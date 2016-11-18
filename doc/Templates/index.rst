**********
Templates
**********

Template merging
==============

StackFormation allows you to configure more than one template:

.. code-block:: yaml
  :emphasize-lines: 4,5

    blueprints:
      - stackname: iam
        template:
          - iam_role_jenkins.template
          - iam_user_inspector.template
        description: 'IAM users and roles'

The template files cannot have duplicate keys in any of the top level attributes. StackFormation will then merge them into a single CloudFormation template and deploy this one instead. This feature helps you to structure your template logically without having to deploy and manage them separatly. Also with this you can choose which template to include in case you're pulling in a StackFormation module like https://github.com/AOEpeople/cfn-lambdahelper.

You can always inspect the final merged and preprocessed template:

.. code-block:: shell

    $ vendor/bin/stackformation.php stack:template iam

Prefixed template merging
^^^^^^^^^^^^^^^^^^^^^^^^^

If you list your templates with attributes instead of a plain list, the attribute keys will be used to prefix every element of that template. This way you can use the same template with different input parameters instead of duplicating resources. This comes in handy for VPC setups.

.. code-block:: yaml

    blueprints:
      - stackname: vpc-subnets
        template:
          ZoneA: az.template
          ZoneB: az.template
        parameters:
          ZoneAVpc: MyVPC
          ZoneAPublicSubnetCidrBlock: '10.0.0.0/24'
          ZoneAPrivateSubnetCidrBlock: '10.0.10.0/24'
          ZoneAAZ: 'eu-west-1a'
          ZoneBVpc: MyVPC
          ZoneBAPublicSubnetCidrBlock: '10.0.1.0/24'
          ZoneBPrivateSubnetCidrBlock: '10.0.11.0/24'
          ZoneBAZ: 'eu-west-1b'
          [...]

If you have a parameter that needs to be passed to all templates you can prefix it with '*' (make sure you add quotes around that key since JSON will consider this a reference instead) and StackFormation will replace '*\ ' with each prefix used in the ``template:`` section.

.. code-block:: yaml
  :emphasize-lines: 7,8

    blueprints:
      - stackname: vpc-subnets
        template:
          ZoneA: az.template
          ZoneB: az.template
        parameters:
          '*Vpc': MyVPC # Will automatically be expanded to 'ZoneAVpc: MyVPC' and 'ZoneBVpc: MyVPC'
          '*Igw': MyInternetGateway
          ZoneAPublicSubnetCidrBlock: '10.0.0.0/24'
          ZoneAPrivateSubnetCidrBlock: '10.0.10.0/24'
          ZoneAAZ: 'eu-west-1a'
          ZoneBVpc: MyVPC
          ZoneBAPublicSubnetCidrBlock: '10.0.1.0/24'
          ZoneBPrivateSubnetCidrBlock: '10.0.11.0/24'
          ZoneBAZ: 'eu-west-1b'
          [...]
          
Inject Parameters
=================

The scripts (included via ``Fn::FileContent``) may contain references to other CloudFormation resources or parameters. Part of the pre-processing is to convert snippets like ``{Ref:MagentoWaitConditionHandle}`` or ``{Ref:AWS::Region}`` or ``{Fn::GetAtt:[resource,attribute]}`` (note the missing quotes!) into correct JSON snippets and embed them into the ``Fn::Join`` array.

Usage Example:

.. code-block:: bash

    #!/usr/bin/env bash
    /usr/local/bin/cfn-signal --exit-code $? '{Ref:WaitConditionHandle}'

will be converted to:

.. code-block:: json

    {"Fn::Join": ["", [
    "#!\/usr\/bin\/env bash\n",
    "\/usr\/local\/bin\/cfn-signal --exit-code $? '", {"Ref": "WaitConditionHandle"}, "'"
    ]]}

Usage Example:

.. code-block:: bash

    #!/usr/bin/env bash
    EIP="{Fn::GetAtt:[NatIp,AllocationId]}"

will be converted to:

.. code-block:: json

    {"Fn::Join": ["", [
    "#!\/usr\/bin\/env bash\n",
    "EIP=\"",
    {
        "Fn::GetAtt": [
            "NatIp",
            "AllocationId"
        ]
    },
    "\"\n",
    ]]}

Include file content
====================

You can include content from a different file into a script. Use this is you have duplicate code that you need to embed into multiple resource's UserData:

Example:

.. code-block:: bash
  :emphasize-lines: 3

    #!/usr/bin/env bash

    ###INCLUDE:../generic/includes/base.sh
    [...]

Inject raw Json
===============

.. code-block:: json

    ###JSON###
    { "hello": "world" }
    ######

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

Comments
========

You can add comments to your JSON file. Due to a current bug you can't have double quotes in your comment block.

Example:

.. code-block:: json

    {"IpProtocol": "tcp", "FromPort": "80", "ToPort": "80", "CidrIp": "1.2.3.4/32"}, /* Office */
    {"IpProtocol": "tcp", "FromPort": "80", "ToPort": "80", "CidrIp": "5.6.7.8/32"}, /* Max Musterman HomeOffice */

Port
====

``"Port":"..."`` will automatically expanded to ``"FromPort": "...", "ToPort": "..."``. So if you're specifying a single port instead of a range of ports you can reduce the redundancy:

Example:

.. code-block:: json

    {"IpProtocol": "tcp", "Port": "80", "CidrIp": "1.2.3.4/32"}, 
    /* expands to: */
    {"IpProtocol": "tcp", "FromPort": "80", "ToPort": "80", "CidrIp": "1.2.3.4/32"},
