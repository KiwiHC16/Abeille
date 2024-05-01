# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
#

import time
import logging
import threading
import requests
import datetime
try:
    from collections.abc import Mapping
except ImportError:
    from collections import Mapping
import serial
import os
from os.path import join
import socket
from queue import Queue
import socketserver
from socketserver import (TCPServer, StreamRequestHandler)
import signal
import unicodedata
import pyudev

# ------------------------------------------------------------------------------

class jeedom_com():
	def __init__(self,apikey = '',url = '',cycle = 0.5,retry = 3):
		self.apikey = apikey
		self.url = url
		self.cycle = cycle
		self.retry = retry
		self.changes = {}
		if cycle > 0 :
			self.send_changes_async()
		logging.info('Init request module v%s', requests.__version__)

	def send_changes_async(self):
		try:
			if len(self.changes) == 0:
				resend_changes = threading.Timer(self.cycle, self.send_changes_async)
				resend_changes.start()
				return
			start_time = datetime.datetime.now()
			changes = self.changes
			self.changes = {}
			logging.info('Send to jeedom: %s', changes)
			i=0
			while i < self.retry:
				try:
					r = requests.post(self.url + '?apikey=' + self.apikey, json=changes, timeout=(0.5, 120), verify=False)
					if r.status_code == requests.codes.ok:
						break
				except Exception as error:
					logging.error('Error on send request to jeedom %s retry : %i/%i', error, i, self.retry)
				i = i + 1
			if r.status_code != requests.codes.ok:
				logging.error('Error on send request to jeedom, return code %i', r.status_code)
			dt = datetime.datetime.now() - start_time
			ms = (dt.days * 24 * 60 * 60 + dt.seconds) * 1000 + dt.microseconds / 1000.0
			timer_duration = self.cycle - ms
			if timer_duration < 0.1 :
				timer_duration = 0.1
			if timer_duration > self.cycle:
				timer_duration = self.cycle
			resend_changes = threading.Timer(timer_duration, self.send_changes_async)
			resend_changes.start()
		except Exception as error:
			logging.error('Critical error on  send_changes_async %s', error)
			resend_changes = threading.Timer(self.cycle, self.send_changes_async)
			resend_changes.start()

	def add_changes(self,key,value):
		if key.find('::') != -1:
			tmp_changes = {}
			changes = value
			for k in reversed(key.split('::')):
				if k not in tmp_changes:
					tmp_changes[k] = {}
				tmp_changes[k] = changes
				changes = tmp_changes
				tmp_changes = {}
			if self.cycle <= 0:
				self.send_change_immediate(changes)
			else:
				self.merge_dict(self.changes,changes)
		else:
			if self.cycle <= 0:
				self.send_change_immediate({key:value})
			else:
				self.changes[key] = value

	def send_change_immediate(self,change):
		threading.Thread( target=self.thread_change,args=(change,)).start()

	def thread_change(self,change):
		logging.info('Send to jeedom : %s', change)
		i=0
		while i < self.retry:
			try:
				r = requests.post(self.url + '?apikey=' + self.apikey, json=change, timeout=(0.5, 120), verify=False)
				if r.status_code == requests.codes.ok:
					break
			except Exception as error:
				logging.error('Error on send request to jeedom %s retry : %i/%i', error, i, self.retry)
			i = i + 1

	def set_change(self,changes):
		self.changes = changes

	def get_change(self):
		return self.changes

	def merge_dict(self,d1, d2):
		for k,v2 in d2.items():
			v1 = d1.get(k) # returns None if v1 has no value for this key
			if isinstance(v1, Mapping) and isinstance(v2, Mapping):
				self.merge_dict(v1, v2)
			else:
				d1[k] = v2

	def test(self):
		try:
			response = requests.get(self.url + '?apikey=' + self.apikey, verify=False)
			if response.status_code != requests.codes.ok:
				logging.error('Callback error: %s %s. Please check your network configuration page', response.status.code, response.status.message)
				return False
		except Exception as e:
			logging.error('Callback result as a unknown error: %s. Please check your network configuration page', e.message)
			return False
		return True

# ------------------------------------------------------------------------------

class jeedom_utils():

	@staticmethod
	def convert_log_level(level = 'error'):
		LEVELS = {'debug': logging.DEBUG,
		  'info': logging.INFO,
		  'notice': logging.WARNING,
		  'warning': logging.WARNING,
		  'error': logging.ERROR,
		  'critical': logging.CRITICAL,
		  'none': logging.CRITICAL}
		return LEVELS.get(level, logging.CRITICAL)

	@staticmethod
	def set_log_level(level = 'error'):
		FORMAT = '[%(asctime)-15s][%(levelname)s] : %(message)s'
		logging.basicConfig(level=jeedom_utils.convert_log_level(level),format=FORMAT, datefmt="%Y-%m-%d %H:%M:%S")

	@staticmethod
	def find_tty_usb(idVendor, idProduct, product = None):
		context = pyudev.Context()
		for device in context.list_devices(subsystem='tty'):
			if 'ID_VENDOR' not in device:
				continue
			if device['ID_VENDOR_ID'] != idVendor:
				continue
			if device['ID_MODEL_ID'] != idProduct:
				continue
			if product is not None:
				if 'ID_VENDOR' not in device or device['ID_VENDOR'].lower().find(product.lower()) == -1 :
					continue
			return str(device.device_node)
		return None

	@staticmethod
	def stripped(str):
		return "".join([i for i in str if i in range(32, 127)])

	@staticmethod
	def ByteToHex( byteStr ):
		return byteStr.hex()

	@staticmethod
	def dec2bin(x, width=8):
		return ''.join(str((x>>i)&1) for i in xrange(width-1,-1,-1))

	@staticmethod
	def dec2hex(dec):
		if dec is None:
			return '0x00'
		return "0x{:02X}".format(dec)

	@staticmethod
	def testBit(int_type, offset):
		mask = 1 << offset
		return(int_type & mask)

	@staticmethod
	def clearBit(int_type, offset):
		mask = ~(1 << offset)
		return(int_type & mask)

	@staticmethod
	def split_len(seq, length):
		return [seq[i:i+length] for i in range(0, len(seq), length)]

	@staticmethod
	def write_pid(path):
		pid = str(os.getpid())
		logging.info("Writing PID %s to %s", pid, path)
		open(path, 'w').write("%s\n" % pid)

	@staticmethod
	def remove_accents(input_str):
		nkfd_form = unicodedata.normalize('NFKD', unicode(input_str))
		return u"".join([c for c in nkfd_form if not unicodedata.combining(c)])

	@staticmethod
	def printHex(hex):
		return ' '.join([hex[i:i + 2] for i in range(0, len(hex), 2)])

# ------------------------------------------------------------------------------

class jeedom_serial():

	def __init__(self,device = '',rate = '',timeout = 9,rtscts = True,xonxoff=False):
		self.device = device
		self.rate = rate
		self.timeout = timeout
		self.port = None
		self.rtscts = rtscts
		self.xonxoff = xonxoff
		logging.info('Init serial module v%s', serial.VERSION)

	def open(self):
		if self.device:
			logging.info("Open serial port on device: %s, rate %s, timeout: %i", self.device, self.rate, self.timeout)
		else:
			logging.error("Device name missing.")
			return False
		logging.info("Open Serialport")
		try:
			self.port = serial.Serial(
			self.device,
			self.rate,
			timeout=self.timeout,
			rtscts=self.rtscts,
			xonxoff=self.xonxoff,
			parity=serial.PARITY_NONE,
			stopbits=serial.STOPBITS_ONE
			)
		except serial.SerialException as e:
			logging.error("Error: Failed to connect on device %s. Details : %s", self.device, e)
			return False
		if not self.port.isOpen():
			self.port.open()
		self.flushOutput()
		self.flushInput()
		return True

	def close(self):
		logging.info("Close serial port")
		try:
			self.port.close()
			logging.info("Serial port closed")
			return True
		except:
			logging.error("Failed to close the serial port (%s)", self.device)
			return False

	def write(self,data):
		logging.info("Write data to serial port: %s", str(jeedom_utils.ByteToHex(data)))
		self.port.write(data)

	def flushOutput(self,):
		logging.info("flushOutput serial port ")
		self.port.flushOutput()

	def flushInput(self):
		logging.info("flushInput serial port ")
		self.port.flushInput()

	def read(self):
		if self.port.inWaiting() != 0:
			return self.port.read()
		return None

	def readbytes(self,number):
		buf = b''
		for i in range(number):
			try:
				byte = self.port.read()
			except IOError as e:
				logging.error("Error: %s", e)
			except OSError as e:
				logging.error("Error: %s", e)
			buf += byte
		return buf

# ------------------------------------------------------------------------------

JEEDOM_SOCKET_MESSAGE = Queue()

class jeedom_socket_handler(StreamRequestHandler):
	def handle(self):
		global JEEDOM_SOCKET_MESSAGE
		logging.info("Client connected to [%s:%d]", self.client_address[0], self.client_address[1])
		lg = self.rfile.readline()
		JEEDOM_SOCKET_MESSAGE.put(lg)
		logging.info("Message read from socket: %s", str(lg.strip()))
		self.netAdapterClientConnected = False
		logging.info("Client disconnected from [%s:%d]", self.client_address[0], self.client_address[1])

class jeedom_socket():

	def __init__(self,address='localhost', port=55000):
		self.address = address
		self.port = port
		socketserver.TCPServer.allow_reuse_address = True

	def open(self):
		self.netAdapter = TCPServer((self.address, self.port), jeedom_socket_handler)
		if self.netAdapter:
			logging.info("Socket interface started")
			threading.Thread(target=self.loopNetServer, args=()).start()
		else:
			logging.info("Cannot start socket interface")

	def loopNetServer(self):
		logging.info("LoopNetServer Thread started")
		logging.info("Listening on: [%s:%d]", self.address, self.port)
		self.netAdapter.serve_forever()
		logging.info("LoopNetServer Thread stopped")

	def close(self):
		self.netAdapter.shutdown()

	def getMessage(self):
		return self.message

# ------------------------------------------------------------------------------
# END
# ------------------------------------------------------------------------------
