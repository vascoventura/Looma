/*
*
LOOMA js code file
Filename: looma-time.js
Description: This program creates eight different clocks on the canvas elements in
looma-clock-worldclocks.php.  Each clock shows the time in a different city.  It updates
each clock every second, and uses window.addEventListener('resize') to allow the clocks to
shrink and grow as the window size changes.

Programmer name: John Weingart and Grant Dumanian
Owner: VillageTech Solutions (villagetechsolutions.org)
Date: 7/12/2016
Revision: Looma 2.0.x
Comments:
-->
*/

window.onload = function() {
    
    toolbar_button_activate("clock");
    
    //canvas set-up
    var nepal = document.getElementById("nepal");
    var ctx1 = nepal.getContext("2d");
    var melbourne = document.getElementById("tokyo");
    var ctx2 = melbourne.getContext("2d");
    var newYork = document.getElementById("newYork");
    var ctx3 = newYork.getContext("2d");
    var paris = document.getElementById("paris");
    var ctx4 = paris.getContext("2d");
    var london = document.getElementById("london");
    var ctx5 = london.getContext("2d");
    var sanFrancisco = document.getElementById("sanFrancisco");
    var ctx6 = sanFrancisco.getContext("2d");
    var moscow = document.getElementById("moscow");
    var ctx7 = moscow.getContext("2d");
    var cairo = document.getElementById("cairo");
    var ctx8 = cairo.getContext("2d");

    var allCanvases = [nepal, melbourne, newYork, paris, london, sanFrancisco, moscow, cairo];
    var allCanvasContexts = [ctx1, ctx2, ctx3, ctx4, ctx5, ctx6, ctx7, ctx8];

    // DST-aware time zones (IANA names)
    var timeZones = ["Asia/Kathmandu", "Asia/Tokyo", "America/New_York",
                     "Europe/Paris", "Europe/London", "America/Los_Angeles", "Europe/Moscow", "Africa/Cairo"];
    var timeFormatters = {};

    function getTimeParts(timeZone, dateObj) {
        if (!timeFormatters[timeZone]) {
            timeFormatters[timeZone] = new Intl.DateTimeFormat('en-US', {
                timeZone: timeZone,
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        var parts = timeFormatters[timeZone].formatToParts(dateObj);
        var out = {hour: 0, minute: 0, second: 0};
        for (var i = 0; i < parts.length; i++) {
            if (parts[i].type === 'hour') out.hour = parseInt(parts[i].value, 10);
            else if (parts[i].type === 'minute') out.minute = parseInt(parts[i].value, 10);
            else if (parts[i].type === 'second') out.second = parseInt(parts[i].value, 10);
        }
        return out;
    }
    var names =       ["Kathmandu, Nepal", "Tokyo, Japan", "New York City, United States",
                       "Paris, France", "London, England", "San Francisco, United States", "Moscow, Russia", "Cairo, Egypt"];
    var nativeNames = ["काठमॉनडु, नेपाल",     "टोक्यो, जापान",   "न्यूयोर्क शहर, संयुक्त राज्य अमेरिका",
                       "पेरिस, फ्रान्स",     "लन्डन, इङ्गल्याण्ड",   "सैन फ्रान्सिस्को, संयुक्त राज्य अमेरिका",  "मास्को, रूस",       "कायरो, इजीपट"];
    
    var num;
    var fullRadius;
    var radius;
    var clockRadiusScale = 0.58;
    var cityNameOffset = 26;
    var digitalTimeOffset = 28;
    
    //resize and translate all canvases
    for(num = 0; num < allCanvasContexts.length; num++) {
        resizeCanvas(allCanvases[num]);
        allCanvasContexts[num].translate(fullRadius, fullRadius);
    }
    radius = fullRadius * clockRadiusScale;
    
    window.addEventListener('resize', changeSize);

    language = LOOMA.readStore('language', 'cookie');
    if (!language) {
        LOOMA.setStore('language', 'english', 'cookie');
        language = 'english';
    };
 
    
    drawAllClocks();
    drawAllNames();
    
    $('#translate').off().click(function() {
        //   location.reload();
        language = LOOMA.readStore('language', 'cookie');
        if (!language) {
            LOOMA.setStore('language', 'english', 'cookie');
            language = 'english';
        };
        language = language === 'english' ? 'native' : 'english';
        LOOMA.translate(language);
        drawAllNames();
    });
    
    var secondtimer = setInterval(drawAllClocks, 1000);
    
    function drawClock(timeZone, ctx) {
        var tp = getTimeParts(timeZone, new Date());
        ctx.clearRect(-fullRadius, -fullRadius, fullRadius * 2, fullRadius * 2);
        drawFace(ctx);
        drawNumbers(ctx);
        drawTicks(ctx, radius * 0.02);
        drawTime(ctx, tp.hour, tp.minute, tp.second);
        drawDigitalTime(tp.hour, tp.minute, ctx);
    }

    function drawAllClocks() {
        for (num = 0; num < allCanvasContexts.length; num++) {
            drawClock(timeZones[num], allCanvasContexts[num]);
        }
        drawAllNames();
    }
    
    function eraseName(ctx, name) {
        ctx.font = "16px Chalkboard";
        ctx.fillStyle = "#091F48";
        ctx.fillText(name, 0, -radius - cityNameOffset);
    }
    
    function drawName(ctx, name) {
        ctx.font = "16px Chalkboard";
        ctx.fillStyle = "yellow";
        ctx.fillText(name, 0, -radius - cityNameOffset);
    }
    
    function drawAllNames() {
        if (language === 'english')
            for (num = 0; num < names.length; num++) {
                eraseName(allCanvasContexts[num], nativeNames[num]);
                drawName(allCanvasContexts[num], names[num]);
            }
        else for (num = 0; num < nativeNames.length; num++) {
            eraseName(allCanvasContexts[num], names[num]);
            drawName(allCanvasContexts[num], nativeNames[num]);
        }
    };
    
  
    function drawFace(ctx) {
        var grad;
        ctx.beginPath();
        ctx.arc(0, 0, radius, 0, 2 * Math.PI);
        ctx.fillStyle = 'white';
        ctx.fill();
        grad = ctx.createRadialGradient(0, 0, radius * 0.95, 0, 0, radius * 1.05);
        grad.addColorStop(0, '#333');
        grad.addColorStop(0.5, 'white');
        grad.addColorStop(1, '#333');
        ctx.strokeStyle = grad;
        ctx.lineWidth = radius * 0.1;
        ctx.stroke();
        ctx.beginPath();
        ctx.arc(0, 0, radius * 0.1, 0, 2 * Math.PI);
        ctx.fillStyle = '#333';
        ctx.fill();
    }

    function drawNumbers(ctx) {
        var ang;
        var num;
        ctx.font = radius * 0.15 + "px arial";
        ctx.textBaseline = "middle";
        ctx.textAlign = "center";
        for (num = 1; num < 13; num++) {
            ang = num * Math.PI / 6;
            ctx.rotate(ang);
            ctx.translate(0, -radius * 0.81);
            ctx.rotate(-ang);
            ctx.fillText(num.toString(), 0, 0);
            ctx.rotate(ang);
            ctx.translate(0, radius * 0.81);
            ctx.rotate(-ang);
        }
    }

    function drawTicks(ctx, width) {
        var ang;
        var num;

        for (num = 1; num < 61; num++) {
            ang = num * Math.PI / 30;
            ctx.beginPath();
            if (num % 5 == 0) {
                ctx.lineWidth = width * 2;
            } else {
                ctx.lineWidth = width;
            }
            ctx.lineCap = "round";
            ctx.rotate(ang);
            ctx.moveTo(0, -radius * 0.92);
            ctx.lineTo(0, -radius);
            ctx.stroke();
            ctx.rotate(-ang);
        }
    }

    function drawTime(ctx, hour, minute, second) {
        var newHour = hour;
        if (newHour > 12) {
            newHour = newHour - 12;
        }
        //hours
        newHour = (newHour * Math.PI / 6) + (minute * Math.PI / (6 * 60));
        drawHand(ctx, newHour, radius * 0.5, radius * 0.07);

        //minute
        minute = (minute * Math.PI / 30);
        drawHand(ctx, minute, radius * 0.8, radius * 0.07);

    }

    function drawHand(ctx, pos, length, width) {
        ctx.beginPath();
        ctx.lineWidth = width;
        ctx.lineCap = "round";
        ctx.moveTo(0, 0);
        ctx.rotate(pos);
        ctx.lineTo(0, -length);
        ctx.stroke();
        ctx.rotate(-pos);
    }
    
    function drawDigitalTime(hours, minutes, ctx) {
        var digitalTime, nepaliTime;
        var printHour = hours;
        var printMinute = minutes;
        if(printMinute < 0) {
            printMinute = 60 + printMinute;
            printHour --;
        }
        else if(printMinute > 59) {
            printMinute = printMinute - 60;
            printHour ++;
        }
        
        if(printMinute < 10) {
            printMinute = "0" + printMinute;
        }
        if(printHour <= 0) {
            printHour += 24;
        }
        if(printHour > 24) {
            printHour = printHour - 24;
        }
        /*NOTE: Nepali am/pm equivalents:
       5:00 AM — 11:59 AM - बिहानको HH:MM
       12:00 PM — 4:59 PM - दिउसोको HH:MM
       5:00 PM — 7:59 PM - बेलुकाको HH:MM
       8:00 PM  — 4:59 AM - रातीको HH:MM
     */
        if(printHour >= 12 && printHour < 24) {
            if(printHour != 12) {
                digitalTime = (printHour - 12) + ":" + printMinute + " PM";
                     if (printHour < 17)      nepaliTime = "दिउसोको " + (printHour - 12) + ":" + printMinute;
                     else if (printHour < 20) nepaliTime = "रातीको "  + (printHour - 12) + ":" + printMinute;
                     else                     nepaliTime = "रातीको "   + (printHour - 12) + ":" + printMinute;
            }
            else {
                digitalTime =           printHour + ":" + printMinute + " PM";
                nepaliTime = "दिउसोको " + printHour + ":" + printMinute;
            }
        }
        else {
            if(printHour == 24) {
                digitalTime =          "12:" + printMinute + " AM";
                nepaliTime = "रातीको " + "12:" + printMinute;
            }
            else {
                digitalTime =                               printHour + ":" + printMinute + " AM";
                if (printHour < 5)  nepaliTime = "रातीको "  + printHour + ":" + printMinute;
                else                nepaliTime = "बिहानको " + printHour + ":" + printMinute;
            }
        }
    
        ctx.fillStyle = "white";
        ctx.font = "16px Chalkboard";
        //ctx.font = "25px Ariel";
        ctx.clearRect(-radius, radius + 4, radius * 2, fullRadius - radius - 4);
        ctx.fillText((language==='english' ? digitalTime : nepaliTime), 0, radius + digitalTimeOffset);
       // ctx.fillText(digitalTime, 0, radius + 12);
    } // end drawDigitalTime()
                            
                            /*    function drawDigitalTime(hours, minutes, ctx) {
                                    ctx.clearRect(-radius * 2 / 3, radius, radius * 1.5, radius / 2);
                                    var printHour = hours;
                                    var printMinute = minutes;
                                    if(printMinute < 0) {
                                        printMinute = 60 + printMinute;
                                        printHour --;
                                    }
                                    else if(printMinute > 59) {
                                        printMinute = printMinute - 60;
                                        printHour ++;
                                    }
                                    
                                    if(printMinute < 10) {
                                        printMinute = "0" + printMinute;
                                    }
                                    if(printHour <= 0) {
                                        printHour += 24;
                                    }
                                    if(printHour > 24) {
                                        printHour = printHour - 24;
                                    }
                                    
                                    ctx.fillStyle = "yellow";
                                    ctx.font = "18px Chalkboard";
                                    //ctx.font = "25px Ariel";
                                    
                                    if(printHour >= 12 && printHour < 24) {
                                        if(printHour != 12) {
                                            ctx.fillText((printHour - 12) + ":" + printMinute + " PM", 0, radius + 12);
                                        }
                                        else {
                                            ctx.fillText(printHour + ":" + printMinute + " PM", 0, radius + 12);
                                        }
                                    }
                                    else {
                                        if(printHour == 24) {
                                            ctx.fillText("12:" + printMinute + " AM", 0, radius + 12);
                                        }
                                        else {
                                            ctx.fillText(printHour + ":" + printMinute + " AM", 0, radius + 12);
                                        }
                                    }
                                }
                              */
    
    function changeSize() {
        var num;
        for(num = 0; num < allCanvases.length; num++) {
            resizeCanvas(allCanvases[num]);
            allCanvasContexts[num].translate(fullRadius, fullRadius);
        }
        radius = fullRadius * clockRadiusScale;

        drawAllClocks();
        drawAllNames();
    }
    
    function resizeCanvas(thisCanvas) {
        var width = window.innerWidth;
        var height = window.innerHeight;
        
        if(width < height)
        {
            thisCanvas.height = width / 3;
            thisCanvas.width = width / 3;
        }
        
        else {
            thisCanvas.height = height / 3;
            thisCanvas.width = height / 3;
        }
        
        fullRadius = thisCanvas.height / 2;
    }
}
