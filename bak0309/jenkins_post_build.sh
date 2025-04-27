set -o errexit

NODE_PATH=/usr/lib/nodejs:/usr/lib/node_modules:/usr/share/javascript
export NODE_PATH
workpath=$(cd `dirname $0`; pwd)
cd $workpath
node build.js