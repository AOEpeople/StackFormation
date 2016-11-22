****
Misc
****

Use the ``jq`` tool to create a simple list of all parameters (almost) ready to paste it in the blueprints.yml

.. code-block:: shell

    $ cat my.template | jq '.Parameters | keys' | sed 's/",/: \'\'/g' | sed 's/"//g'
