FROM php:7.4.33-apache
RUN pecl install mongodb-1.15.0
RUN apt-get update
RUN apt-get install -y net-tools
RUN apt-get install -y python3
RUN apt-get install -y python3-pip
RUN pip3 install torch>0+cpu -f https://download.pytorch.org/whl/torch_stable.html # this is necessary to avoid downloading unwanted NVIDIA libraries
RUN pip3 install langchain_huggingface qdrant_client sentence-transformers
COPY load_models.py load_models.py
RUN mkdir -p /tmp/.cache/hf
RUN export HF_HOME=/tmp/.cache/hf; python3 load_models.py
RUN chmod -R 777 /tmp/.cache/hf
COPY docker_httpd.conf /etc/apache2/apache2.conf
RUN mkdir -p /usr/local/var/www/Looma
COPY . /usr/local/var/www/Looma
RUN chown -R www-data:www-data /usr/local/var/www/Looma && chmod -R a+rX /usr/local/var/www/Looma

COPY docker_php.ini /usr/local/etc/php/php.ini
COPY launch.sh /bin/launch.sh
RUN pip3 install flask
RUN chmod +x /bin/launch.sh

# Download and install Piper TTS
RUN apt-get update && apt-get install -y --no-install-recommends wget unzip curl libcurl4 && rm -rf /var/lib/apt/lists/*
RUN apt-get install -y --reinstall --no-install-recommends curl libcurl4 && rm -rf /var/lib/apt/lists/*
RUN dpkg --print-architecture
RUN ARCH=$(dpkg --print-architecture); wget https://github.com/rhasspy/piper/releases/download/v1.2.0/piper_$ARCH.tar.gz -O /tmp/piper.tar.gz
RUN tar -xzf /tmp/piper.tar.gz -C /usr/local/bin
RUN rm /tmp/piper.tar.gz

# Download Piper "low" quality voice models with retries because Hugging Face
# occasionally returns transient 5xx errors. Nepali's lowest published quality
# is "x_low"; English (amy) uses "low".
RUN set -eux; \
    mkdir -p /usr/share/piper; \
    download() { \
        url="$1"; \
        output="$2"; \
        curl -fL \
            --retry 8 \
            --retry-delay 5 \
            --retry-all-errors \
            "$url" \
            -o "$output"; \
    }; \
    download https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/ne/ne_NP/google/x_low/ne_NP-google-x_low.onnx /usr/share/piper/ne_NP-google-x_low.onnx; \
    download https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/ne/ne_NP/google/x_low/ne_NP-google-x_low.onnx.json /usr/share/piper/ne_NP-google-x_low.onnx.json; \
    download https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/en/en_US/amy/low/en_US-amy-low.onnx /usr/share/piper/en_US-amy-low.onnx; \
    download https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/en/en_US/amy/low/en_US-amy-low.onnx.json /usr/share/piper/en_US-amy-low.onnx.json

# fill in XXX to load more voices
# RUN    wget https://huggingface.co/rhasspy/piper-voices/blob/main/en/XXX       -O /usr/share/piper/XXX \
#     && wget https://huggingface.co/rhasspy/piper-voices/blob/main/en/XXX.json  -O /usr/share/piper/XXX.json

# Add Piper to PATH
ENV PATH="/usr/local/bin/piper:${PATH}"

ENV DOCKER=1
CMD ["/bin/launch.sh"]
