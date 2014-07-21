@echo off
copy /b jquery.fancybox.js + s.js temp.js

"C:\Program Files\Java\jre6\bin\java.exe" -jar C:\Programs\YUICompressor\build\yuicompressor-2.4.8.jar --type js -o s.min.js temp.js
del temp.js


