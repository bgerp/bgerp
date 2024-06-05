# Инструкции за инсталиране на БГ речник за Ubuntu 22.04

```
# wget https://ftp.gnu.org/gnu/aspell/dict/bg/aspell6-bg-4.1-0.tar.bz2
# tar -xf aspell6-bg-4.1-0.tar.bz2
# cd aspell6-bg-4.1-0
# apt install make
# ./configure
# make
# make install
 ```
```
aspell -l bg dump master | grep здравей
```
Трябва да върне:

```
#здравей
#здравейте
```
