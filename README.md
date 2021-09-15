# Nextcloud App: checksum_api
Nextcloud application for checksum API.

# Function Overview

Add API to calculate hash value of specified file,
which save a hash value to database as cache.
Provide hash values of a file with REST API.
query "hash" tag,sets MD5/SHA256/SHA512.

# Usage

## Method 

 GET 

## URL

 https://<nextcloud_server>/ocs/v2.php/apps/nextcloud-checksum/api/checksum

## Query Parameters:

path: set file path.
revision: set file revision.
hash: MD5,SHA256,SHA512

## Example of API Call

```
URL="https://nextcloud.example.com"
APIPATH="/ocs/v2.php/apps/checksum_api/api/checksum"
FILEPATH="/abc.txt"

curl -u test2:password \
-H 'OCS-APIRequest: true' \
-X GET "${URL}${APIPATH}?path=${FILEPATH}&hash=md5,sha256,sha512"
```

## Example of API Call Response

```
<?xml version="1.0"?>
<ocs>
 <meta>
  <status>ok</status>
  <statuscode>200</statuscode>
  <message>OK</message>
 </meta>
 <data>
  <hash>
   <md5>1a92e9ace9f32788e42d3893ff03dcb8</md5>
   <sha256>663781f05a57c0d68fbc248c2c75c25b84b0626507c3e9b109b25b4ab95c2ac8</sha256>
   <sha512>50cb31e093e0a2c5946c198374834d9a5a38f146c14c0045d6c10636fb99f53fcaa545b698e041eb2af4db3f309a45dc122706576c754b257d60386572432515</sha512>
  </hash>
 </data>
</ocs>
```
