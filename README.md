eLife Search
=============

Dependencies
------------

* [Composer](https://getcomposer.org/)
* [npm](https://www.npmjs.com/)
* [PHP 7](https://www.php.net/)
* [Docker](https://www.docker.com/)
* [Docker-compose](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-20-04)

<details>
<summary>Windows - tips and tricks</summary>
When using Windows to bypass the main errors we recommend to follow the next :

1. Before you cloned the repo, make sure that you configure git to use the correct line endings.

    * [Explanation](https://stackoverflow.com/a/71209401) / [More detailed](https://stackoverflow.com/q/10418975)
    * Easy fix : `git config --global core.autocrlf input`

2. Make sure you use Windows Linux Subsystem (WSL) or at least git bash.

    * [Guide to use WSL](https://adamtheautomator.com/windows-subsystem-for-linux/)
    * [Guide to use Git Bash](https://www.geeksforgeeks.org/working-on-git-bash/)
</details>

Docker Installation
-------------------

1. Run `docker-compose up --build`

Important : Rename `config.php.xml` on local to `config.xml` because config.php is in .gitignore.
