ECGPB Member List Administration
================================

This application is written for the christian church
[Evangeliums-Christengemeinde e.V.](http://www.ecgpb.de) and its main purpose
is the administration of its members and to generate a printable member list.


Login
-----

The user information are stored in `app/config/security.yml` and the passwords
are saved in `app/config/parameters.yml` as SHA1 encrypted hashes.

If the password is not remembered anymore, one could call `http://web-url.com/index/encode_password`
to generate a new password. But it must be saved manually in `app/config/parameters.yml`.