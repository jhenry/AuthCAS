# CAS Auth for CumulusClips

This is a plugin to provide simple CAS authentication for the CumulusClips application.  

## Configuration

Depending on your environment, the top of your .htaccess might look something like the below.

```apache
<Files login>
AuthType CAS
require valid-user
</Files>

<Files actions>
AuthType CAS
require valid-user
</Files>

<Files cc-admin>
AuthType CAS
require valid-user
</Files>
```