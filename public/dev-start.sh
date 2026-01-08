#!/bin/sh
echo "Killing Octane process on port 8000..."
PID=$(lsof -t -i:8000)
if [ "$PID" != "" ]; then
  kill -9 $PID
  echo "Killed process $PID"
fi
php artisan octane:start --host=0.0.0.0 --port=8000 --watch