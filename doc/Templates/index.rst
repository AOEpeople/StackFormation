**********
Templates
**********

Using composer
==============

You can pull in StackFormation modules via composer. Look at the `cfn-lambdahelper <https://github.com/AOEpeople/cfn-lambdahelper>`__ for an example. A custom composer installer (configured as ``require`` dependency) will take care of putting all the module files in your ``blueprints/`` directory. This way you can have project specific and generic modules next to each other.

Please note that a "StackFormation module" will probably not come with a ``blueprints.yml`` file since this (and especially the stack parameter configuration) is project specific.

You will need to create the stack configuration for the parts you want to use. A good place would be ``blueprints/blueprints.yml`` where you reference the imported module.

Example:

.. code-block:: yaml
  :emphasize-lines: 3
  
    blueprints:
      - stackname: 'lambdacfnhelpers-stack'
        template: 'cfn-lambdahelper/lambda_cfn_helpers.template'
        Capabilities: CAPABILITY_IAM
