Autoload
========

.. image:: image.png
    :alt: Aplus Framework Autoload Library

Aplus Framework Autoload Library.

- `Installation`_
- `Autoloader`_
- `Locator`_
- `Preloader`_
- `Conclusion`_

Installation
------------

The installation of this library can be done with Composer:

.. code-block::

    composer require aplus/autoload

Autoloader
----------

`Autoload <https://www.php.net/manual/en/language.oop5.autoload.php>`_
makes it possible to load files with classes, interfaces, traits and
enums automatically if they are not declared.

This library allows `Autoload Classes`_ and `Autoload with Namespaces`_.

For this, the Autoloader class is instantiated as shown in the following example:

.. code-block:: php

    use Framework\Autoload\Autoloader;

    $autoloader = new Autoloader();

It has two parameters. The first causes the class to be registered as an autoloader
and the second the file extensions that can be loaded, which by default is ``.php``.

Register
########

If the registration is not performed through the constructor, you can register
whenever you want through the ``register`` method:

.. code-block:: php

    $autoloader->register();

Once this is done, the classes registered in the Autoloader can be automatically
loaded.

Autoload Classes
################

To register the name of a class that is in a given file, we can use the
``setClass`` method:

Set Class
*********

.. code-block:: php

    $name = 'App';
    $filepath = __DIR__ . '/App.php';
    $autoloader->setClass($name, $filepath);

Set Classes
***********

Or, register multiple classes at once with ``setClasses``:

.. code-block:: php

    $classes = [
        'App' => __DIR__ . '/App.php',
        'Config' => __DIR__ . '/Config.php',
    ];
    $autoloader->setClasses($classes);

Get Class
*********

To get the file path of a registered class, you can use the ``getClass`` method:

.. code-block:: php

    $autoloader->getClass($name); // string or null

Get Classes
***********

And, to get an array with the class names as keys and the file paths as values,
use the ``getClasses`` method:

.. code-block:: php

    $autoloader->getClasses(); // array of strings

Remove Class
************

If you need to remove a class from the Autoloader, use the ``removeClass`` method:

.. code-block:: php

    $name = 'App';
    $autoloader->removeClass($name);

Remove Classes
**************

Or ``removeClasses`` to remove multiple classes at once:

.. code-block:: php

    $names = [
        'App',
        'Config',
    ];
    $autoloader->removeClasses($names);

Autoload with Namespaces
########################

Registering classes individually is great if the files are in different
directories or the file names are inconsistent.

However, a much more powerful way to load classes is to register namespaces
for directories.

Inside this directories, files with the name of the requested class will be
searched and, if found, will be loaded.

Add Namespace
*************

Let's see how to add namespaces in Autoloader:

.. code-block:: php

    $namespace = 'App';
    $directory = __DIR__ . '/app';
    $autoloader->addNamespace($namespace, $directory);

This causes Autoloader to look for classes starting with the ``App`` namespace
within the ``__DIR__ . '/app'`` directory.

Set Namespace
*************

Instead of adding namespaces, it may be necessary to set namespaces, removing
all others. For this, use the ``setNamespace`` method:

.. code-block:: php

    $autoloader->setNamespace($namespace, $directory);

Get Namespace
*************

To know in which directory a namespace is looking for files we use the
``getNamespace`` method. Which will return an array with the directories of a
namespace.

Let's see the example below, getting the directories from the **App** namespace:

.. code-block:: php

    $directories = $autoloader->getNamespace('App'); // array of strings

Set Namespaces
**************

Also, it's possible to have multiple namespaces pointing to directories at once
with the ``setNamespaces`` method.

Let's see how to set a directory for the **App** namespace and another for
**Config**:

.. code-block:: php

    $autoloader->setNamespaces([
        'App' => __DIR__ . '/app',
        'Config' => __DIR__ . '/config',
    ]);

Get Namespaces
**************

To get all the namespaces, use the ``getNamespaces`` method:

.. code-block:: php

    $namespaces = $autoloader->getNamespaces(); // array of array of strings

Remove Namespace
****************

If necessary, a namespace can be removed as in the example below:

.. code-block:: php

    $autoloader->removeNamespace('App');

Remove Namespaces
*****************

Or remove multiple at once:

.. code-block:: php

    $autoloader->removeNamespaces([
        'App',
        'Config',
    ]);

Find Class Path
***************

With Autoloader it is possible to obtain the file path that a class has.
Let's see:

.. code-block:: php

    $filepath = $autoloader->findClassPath('App\Models\Users'); // string or null

Locator
-------

Locator makes it easy to find and list files in certain directories or namespaces.

To instantiate it you need an instance of Autoloader. Let's see:

.. code-block:: php

    use Framework\Autoload\Autoloader;
    use Framework\Autoload\Locator;

    $autoloader = new Autoloader();
    $locator = new Locator($autoloader);

Once this is done, we can locate files and get information about them.

Get Class Name
##############

With Locator we can get the class name of a file that contains a class,
interface, trait or enum.

Let's say there is a **app/Models/Users.php** file:

.. code-block:: php

    <?php

    namespace App\Models;

    class Users
    {
        //
    }

To find the Qualified Class Name in this file, we could use the ``getClassName``
method. For example:

.. code-block:: php

    $filename = __DIR__ . '/app/Models/Users.php';
    $className = $locator->getClassName($filename); // string or null

Which would return **App\Models\Users**.

Locate Files
############

In Locator, there are similar methods, but with slightly different features.

You can get a namespaced path, find files within namespaces, files within
subdirectories within namespaces, and files everywhere.

Get Namespaced Filepath
***********************

Get the first filename found in namespaces with the ``getNamespacedFilepath`` method:

.. code-block:: php

    $file = 'Tests/Foo';
    $filepath = $locator->getNamespacedFilepath($file, '.php'); // string or null

Find Files
**********

To find all files with the same name within all namespaces we can use the
``findFiles`` method:

.. code-block:: php

    $file = 'Foo';
    $files = $locator->findFiles($filename, '.php'); // string or null

Get Files
*********

To get a list of all files within a subdirectory within namespaces we can use the
``getFiles`` method:

.. code-block:: php

    $subDirectory = 'tests';
    $files = $locator->getFiles($subDirectory, '.php'); // string or null

List Files
**********

To list absolutely all the files inside a directory, we can use the
``listFiles`` method:

.. code-block:: php

    $directory = 'tests';
    $files = $locator->listFiles($directory); // array or null

Preloader
---------

`Preloading <https://www.php.net/manual/en/opcache.preloading.php>`_ makes it
possible to load classes into memory, as if they were part of the PHP core. 
Once loaded, they will be available on all requests.

To load the Aplus Framework class files, just use the file with the Preloader
class and call the ``load`` method.

To load the Aplus Framework class files, create a file like **preload.php**:


.. code-block:: php

    <?php

    require __DIR__ . '/vendor/aplus/autoload/src/Preloader.php';

    use Framework\Autoload\Preloader;

    $preloader = new Preloader();
    $preloader->load();

Then, edit the PHP-FPM **php.ini** file by setting the preload file path and,
if necessary, the user:

.. code-block:: ini

    opcache.preload = /path/to/preload.php
    opcache.preload_user = www-data

Autoloader Instance
###################

It is possible to pass an Autoloader instance into the Preloader constructor.

By doing this, all classes set directly or through namespaces will be included
for loading.

That way you can add classes that don't belong to the Framework.

.. code-block:: php

    use Framework\Autoload\Autoloader;
    use Framework\Autoload\Preloader;

    $autoloader = new Autoloader();
    $autoloader->addNamespace('Foo', __DIR__ . '/foo');

    $preloader = new Preloader($autoloader);
    $preloader->load();

Packages
########

The packages directory is defined by default in the Preloader class's constructor.

The default directory is: ``__DIR__ . '/../../'``. Which is compatible with the
structure created by Composer.

Packages Directory
******************

If necessary, you can set a different path to the parent directory of the
framework packages:

.. code-block:: php

    $packagesDir = __DIR__ . '/aplus';
    $preloader = new Preloader($autoloader, $packagesDir);

Get Packages Dir
****************

To get the current packages directory use ``getPackagesDir``:

.. code-block:: php

    $packagesDir = $preloader->getPackagesDir(); // string

Set Packages Dir
****************

Preloader can be instantiated without a packages directory.

To do so, set ``packagesDir`` to ``null`` which will prevent Framework packages
from being loaded.

.. code-block:: php

    $preloader = new Preloader(packagesDir: null);

The packages directory can be set after the construction of the object with
the method ``setPackagesDir``...

.. code-block:: php

    $directory = __DIR__ . '/aplus';
    $preloader->setPackagesDir($directory);

With Packages
*************

If the construction is carried out without the packages directory, it will be
necessary to define that the packages must be loaded with the `withPackages`` method:

.. code-block:: php

    $preloader->setPackagesDir($directory)->withPackages()->load();

With Dev Packages
*****************

To load development packages, such as **Coding Standard** and **Testing**,
use the ``withDevPackages`` method:

.. code-block:: php

    $preloader->withDevPackages()->load();

Preload Files
#############

Preloader can list only framework files to load or list all files.

List Packages Files
*******************

To list only Aplus Framework package files, use the ``listPackagesFiles`` method:

.. code-block:: php

    $files = $preloader->listPackagesFiles(); // array of strings

List Files
**********

To list all the files that will be loaded, use the ``listFiles`` method:

.. code-block:: php

    $files = $preloader->listFiles(); // array of strings

Load
####

To load files into OPCache Preloading, just call the ``load`` method.

.. code-block:: php

    $files = $preloader->load();

It will load all files from `List Files`_ into memory.

Declarations
############

Through Preloader it is possible to obtain which classes, interfaces and traits
are declared.

Get All Declarations
********************

To get all declarations, use ``getAllDeclaration``:

.. code-block:: php

    $allDeclarations = $preloader::getAllDeclarations(); // array of strings

Get Declarations
****************

To get only Aplus Framework declarations, use the method
``getDeclarations`:

.. code-block:: php

    $declarations = $preloader::getDeclarations(); // array of strings

Conclusion
----------

Aplus Autoload Library is an easy-to-use tool for, beginners and experienced, PHP developers. 
It is perfect for autoload, locate files and optimize performance with preload. 
The more you use it, the more you will learn.

.. note::
    Did you find something wrong? 
    Be sure to let us know about it with an
    `issue <https://gitlab.com/aplus-framework/libraries/autoload/issues>`_. 
    Thank you!
