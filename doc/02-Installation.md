# Installation <a id="installation"></a>

These are the instructions for manual Director installations. You can
learn more about how to automate this in the [automation](03-Automation.md) section
of this documentation. In case you already installed Director and want to upgrade
to the latest version, please [read on here](05-Upgrading.md).

## Requirements <a id="installation-requirements"></a>

* Icinga 2 (&gt;= 2.6.0)
  * It is recommended to use the latest feature release of Icinga 2
  * All versions since 2.4.3 should also work fine, but
    we do no longer test and support them.
  * Some features require newer Icinga 2 releases
    * Flapping requires 2.8 for the thresholds to work - and at least 2.7 on all
      nodes
* Icinga Web 2 (&gt;= 2.4.1). All versions since 2.2 should also work fine, but
  might show smaller UI bugs and are not actively tested
* A database, MySQL (&gt;= 5.1) or PostgreSQL (&gt;= 9.1). MariaDB and other
  MySQL forks are also fine. Mentioned versions are the required minimum,
  for MySQL we suggest using at least 5.5.3, for PostgreSQL 9.4.
* PHP (>= 5.4). For best performance please consider use 7.x
* php-curl

## Database Setup <a id="installation-database"></a>

### Create an empty Icinga Director database <a id="installation-database-create"></a>

HINT: You should replace `some-password` with a secure custom password.

#### MySQL (or MariaDB) <a id="installation-database-create-mysql"></a>

```
mysql -e "CREATE DATABASE director CHARACTER SET 'utf8';
   GRANT ALL ON director.* TO director@localhost IDENTIFIED BY 'some-password';"
```

In case your MySQL root user is password-protected, please add `-p` to this
command.

#### PostgreSQL <a id="installation-database-create-pgsql"></a>

```
psql -q -c "CREATE DATABASE director WITH ENCODING 'UTF8';"
psql director -q -c "CREATE USER director WITH PASSWORD 'some-password';
GRANT ALL PRIVILEGES ON DATABASE director TO director;
CREATE EXTENSION pgcrypto;"
```

> **Hint:**
>
> pgcrypto helps to boost performance, but is currently optional. In case you
> do not have it available on your platform and/or do not know how to solve this
> just leave away the 'CREATE EXTENSION' part.

### Create a Database resource <a id="installation-web-config-db-resource"></a>

The following steps should guide you through the web-based Kickstart wizard.
In case you prefer automated configuration, you should check the dedicated
[documentation section](03-Automation.md).

In your web frontend please go to `Configuration > Application > Resources`
and create a new database resource pointing to your newly created database,
e.g. `director`.

> **Note**
>
> Please make sure that you choose `utf8` as an encoding.


## Install the Director module <a id="installation-module"></a>

As with any Icinga Web 2 module, installation is pretty straight-forward. In
case you're installing it from source all you have to do is to drop the director
module in one of your module paths. You can examine (and set) the module path(s)
in `Configuration / Application`. In a typical environment you'll probably drop the
module to `/usr/share/icingaweb2/modules/director`. Please note that the directory
name MUST be `director` and not `icingaweb2-module-director` or anything else.

### Installation from release tarball <a id="installation-tarball"></a>

Download the [latest version](https://github.com/Icinga/icingaweb2-module-director/releases)
and extract it to a folder named `director` in one of your Icinga Web 2 module path directories.

You might want to use a script as follows for this task:

```sh
ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
REPO_URL="https://github.com/icinga/icingaweb2-module-director"
TARGET_DIR="${ICINGAWEB_MODULEPATH}/director"
MODULE_VERSION="1.5.1"
URL="${REPO_URL}/archive/v${MODULE_VERSION}.tar.gz"
install -d -m 0755 "${TARGET_DIR}"
wget -q -O - "$URL" | tar xfz - -C "${TARGET_DIR}" --strip-components 1
```

### Installation from GIT repository <a id="installation-git"></a>

Another convenient method is the installation directly from our GIT repository.
Just clone the repository to one of your Icinga Web 2 module path directories.
It will be immediately ready for use:

```sh
ICINGAWEB_MODULEPATH="/usr/share/icingaweb2/modules"
REPO_URL="https://github.com/icinga/icingaweb2-module-director"
TARGET_DIR="${ICINGAWEB_MODULEPATH}/director"
MODULE_VERSION="1.5.1"
git clone "${REPO_URL}" "${TARGET_DIR}"
```

You can now directly use our current GIT master or check out a specific version.

```
cd "${TARGET_DIR}" && git checkout "v${MODULE_VERSION}"
```

### Enable the newly installed module <a id="installation-module-enable"></a>

Enable the `director` module either on the CLI by running

```sh
icingacli module enable director
```

Or go to your Icinga Web 2 frontend, choose `Configuration > Modules`,
select the `director` module and choose `State: enable`.

### Run the graphical kickstart wizard <a id="installation-kickstart-wizard"></a>

Choose either `Icinga Director` directly from the main menu or
navigate into `Configuration > Modules > director` and select the `Configuration`
tab.

Either way you'll reach the kickstart wizards. Follow the instructions and
you're all done!
