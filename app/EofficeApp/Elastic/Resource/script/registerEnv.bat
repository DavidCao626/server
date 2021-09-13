@echo off
::设置要永久加入到path环境变量中的路径

%1 %2
ver|find "5.">nul&&goto :st
mshta vbscript:createobject("shell.application").shellexecute("%~s0","goto :st","","runas",1)(window.close)&goto :eof

:st
copy "%~0" "%windir%\system32\"


set Driver=%~d0

set My_PATH=%Driver%\e-office10\java\jre

set JAVA_HOME=%My_PATH%;%JAVA_HOME%
reg add "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v "JAVA_HOME" /t REG_EXPAND_SZ /d "%JAVA_HOME%" /f
exit