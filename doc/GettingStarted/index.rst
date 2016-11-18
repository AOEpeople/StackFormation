Getting Started
***************

Installation
============

Via composer
------------

`Install composer <https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx>`__ first, then:

.. code-block:: shell

    $ composer require aoepeople/stackformation

Using the phar
--------------

Grab the latest release from https://github.com/AOEpeople/StackFormation/releases/latest or use this shortcut (requires ``jq`` to be installed)

.. code-block:: shell

    $ wget $(curl -s https://api.github.com/repos/AOEpeople/StackFormation/releases/latest | jq -r '.assets[0].browser_download_url')


.. tip::
    If you want to use StackFormation globally:

    .. code-block:: shell

        $ mv stackformation.phar /usr/local/bin/stackformation
        $ chmod +x /usr/local/bin/stackformation
        
        
Quickstart
==========

AWS access keys
---------------

Create a ``.env.default`` file (and add it yo your gitignore: ``echo .env.default >> .gitignore``)

::

    AWS_ACCESS_KEY_ID=INSERT_YOUR_ACCESS_KEY_HERE
    AWS_SECRET_ACCESS_KEY=INSERT_YOUR_SECRET_KEY_HERE
    AWS_DEFAULT_REGION=INSERT_YOUR_DEFAULT_REGION_HERE
    
Create a blueprint
------------------

Create a ``blueprints.yml`` in your project directory:

.. code-block:: yaml

    blueprints:
      - stackname: my-stack
        template: my-stack.template

Create a CloudFormation template
--------------------------------

Create a CloudFormation template ``my-stack.template`` in your project directory:

.. code-block:: json

    {
      "AWSTemplateFormatVersion": "2010-09-09",
      "Resources": { 
        "MyResource1": { "Type": "AWS::CloudFormation::WaitConditionHandle" }
      }
    }

Deploy your stack
-----------------

.. code-block:: shell

    $ bin/stackformation.php deploy my-stack