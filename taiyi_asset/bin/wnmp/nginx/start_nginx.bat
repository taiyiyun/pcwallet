@echo off
set Path=%SystemRoot%\system32;%SystemRoot%;%SystemRoot%\System32\Wbem

"../../RunHiddenConsole.exe" "../php5/php-cgi.exe" -b 127.0.0.1:9000 -c "../php5/php.ini"
"../../RunHiddenConsole.exe" "../php5/php-cgi.exe" -b 127.0.0.1:9001 -c "../php5/php.ini"
"../../RunHiddenConsole.exe" "../php5/php-cgi.exe" -b 127.0.0.1:9002 -c "../php5/php.ini"
"../../RunHiddenConsole.exe" "../php5/php-cgi.exe" -b 127.0.0.1:9003 -c "../php5/php.ini"
"../../RunHiddenConsole.exe" "../php5/php-cgi.exe" -b 127.0.0.1:9004 -c "../php5/php.ini"




"../../RunHiddenConsole.exe" "nginx.exe" -p .




