# php-chat-server
version 1.0

## Setup
- Need to know how to setup ZF2 using composer
- Create a simple Database with 'user' table, and id,username columns
- create config/autoload/local.php and define db settings, check zf2 docs
- make sure the port 2852 is open for inboun traffic , or what port you specify should be open.
 
## Server
- once zf2 is setup use below command to start server
- php public/index.php chat start
- server will start on 2852, you can modify port in codes

## Client
On terminal use telnet OR nc command e.g.
- nc chat-server-ip-address 2852 
- OR
- telnet chat-server-ip-address 2852

## Command structure
- All commands start with 3 Char e.g.
- LGN = login e.g. LGN2 , LGN3  where 2,3 are user.id in your database
- RST = Send Chat Request  e.g. RST3
- APR = Approve Chat Request e.g. APR3
- LST = List all online users e.g. LST
- LGT = Logout  e.g. LGT
- Most of commands return json which can be proceed as needed

## How to send Chat Messages
2:hiii  here 2 is user.id follow by colon and message

## Demo
- type below command in your terminal
- nc switchcodes.com 2852
- then type below command to login as user 4
- LGN4



  
