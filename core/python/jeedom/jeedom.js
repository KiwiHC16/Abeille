/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

const axios = require('axios');
var express = require('express');

var Jeedom = {}
Jeedom.log = {}
Jeedom.com = {}
Jeedom.http = {}

/***************************ARGS*******************************/

Jeedom.getArgs = function() {
  var result = {}
  var args = process.argv.slice(2,process.argv.length);
  for (var i = 0, len = args.length; i < len; i++) {
    if (args[i].slice(0,2) === '--') {
      result[args[i].slice(2,args[i].length)] = args[i + 1]
    }
  }
  return result
}

/***************************LOGS*******************************/

Jeedom.log.setLevel = function(_level){
  var convert = {debug  : 0,info : 10,notice : 20,warning : 30,error : 40,critical : 50,none : 60}
  Jeedom.log.level = convert[_level]
}

Jeedom.log.debug  = function(_log){
  if(Jeedom.log.level > 0){
    return;
  }
  console.log('['+(new Date().toISOString().replace(/T/, ' ').replace(/\..+/, ''))+'][DEBUG] : '+_log)
}

Jeedom.log.info  = function(_log){
  if(Jeedom.log.level > 10){
    return;
  }
  console.log('['+(new Date().toISOString().replace(/T/, ' ').replace(/\..+/, ''))+'][INFO] : '+_log)
}

Jeedom.log.error  = function(_log){
  if(Jeedom.log.level > 40){
    return;
  }
  console.log('['+(new Date().toISOString().replace(/T/, ' ').replace(/\..+/, ''))+'][ERROR] : '+_log)
}

/***************************PID*******************************/

Jeedom.write_pid = function(_file){
  var fs = require('fs');
  fs.writeFile(_file, process.pid.toString(), function(err) {
    if(err) {
      Jeedom.log.error("Can't write pid file : "+err);
      process.exit()
    }
  });
}

/***************************COM*******************************/

Jeedom.isObject = function(item) {
  return (item && typeof item === 'object' && !Array.isArray(item));
}

Jeedom.mergeDeep = function(target, ...sources) {
  if (!sources.length) return target;
  const source = sources.shift();
  if (Jeedom.isObject(target) && Jeedom.isObject(source)) {
    for (const key in source) {
      if (Jeedom.isObject(source[key])) {
        if (!target[key]) Object.assign(target, { [key]: {} });
        Jeedom.mergeDeep(target[key], source[key]);
      } else {
        Object.assign(target, { [key]: source[key] });
      }
    }
  }
  return Jeedom.mergeDeep(target, ...sources);
}

Jeedom.com.config = function(_apikey,_callback,_cycle){
  Jeedom.com.apikey = _apikey;
  Jeedom.com.callback = _callback;
  Jeedom.com.cycle = _cycle;
  Jeedom.com.changes = {};
  if(Jeedom.com.cycle > 0){
    setInterval(function() {
      if(Object.keys(Jeedom.com.changes).length > 0){
        Jeedom.com.send_change_immediate(Jeedom.com.changes);
        Jeedom.com.changes = {};
      }
    }, Jeedom.com.cycle * 1000);
  }
}

Jeedom.com.add_changes = function(_key,_value){
  if (_key.indexOf('::') != -1){
    tmp_changes = {}
    var changes = _value
    var keys = _key.split('::').reverse();
    for (var k in keys){
      if (typeof tmp_changes[keys[k]] == 'undefined'){
        tmp_changes[keys[k]] = {}
      }
      tmp_changes[keys[k]] = changes
      changes = tmp_changes
      tmp_changes = {}
    }
    if (Jeedom.com.cycle <= 0){
      Jeedom.com.send_change_immediate(changes)
    }else{
      Jeedom.com.changes = Jeedom.mergeDeep(Jeedom.com.changes,changes)
    }
  } else{
    if (Jeedom.com.cycle <= 0){
      Jeedom.com.send_change_immediate({_key:_value})
    }else{
      Jeedom.com.changes[_key] = _value
    }
  }
}

Jeedom.com.send_change_immediate = function(_changes){
  Jeedom.log.debug('Send data to jeedom : '+JSON.stringify(_changes));
  axios({
    method : 'POST',
    url:Jeedom.com.callback+'?apikey='+Jeedom.com.apikey,
    data: JSON.stringify(_changes)
  }).catch(function (error) {
      Jeedom.log.error('Error on send to jeedom : '+JSON.stringify(error));
  })
}

Jeedom.com.test = function(){
  axios({
    method:'GET',
    url:Jeedom.com.callback+'?apikey='+Jeedom.com.apikey
  }).catch(function (error) {
    Jeedom.log.error('Callback error.Please check your network configuration page : '+JSON.stringify(error));
    process.exit();
  })
}

/***************************HTTP SERVER*******************************/

Jeedom.http.config = function(_port,_apikey){
  Jeedom.http.apikey = _apikey;
  Jeedom.http.app = express();
  Jeedom.http.app.use(express.urlencoded({limit: '5mb'}));
  Jeedom.http.app.use(express.json({limit: '5mb'}));
  Jeedom.http.app.get('/', function(req, res) {
    res.setHeader('Content-Type', 'text/plain');
    res.status(404).send('Not found');
  });
  Jeedom.http.app.listen(_port,'127.0.0.1', function() {
    Jeedom.log.debug('HTTP listen on 127.0.0.1 port : '+_port+' started');
  });
}

Jeedom.http.checkApikey = function(_req){
  return (_req.query.apikey === Jeedom.http.apikey)
}

/***************************EXPORTS*******************************/

exports.getArgs = Jeedom.getArgs;
exports.log = Jeedom.log;
exports.write_pid = Jeedom.write_pid;
exports.com = Jeedom.com;
exports.http = Jeedom.http;
