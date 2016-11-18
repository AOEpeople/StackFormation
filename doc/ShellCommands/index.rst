**************
Shell commands
**************

You can run shell commands before or/and after the CloudFormation is being deployed. The commands will be executed in the directory where the blueprints.yml file lives.

Before
======

Example:

.. code-block:: yaml
  :emphasize-lines: 5,6,7,8,9

    blueprints:
      - stackname: 'my-lambda-function'
        template: lambda.template
        Capabilities: CAPABILITY_IAM
        before:
        - cd function 
        - npm install aws-sdk
        - zip -r nat_gateway.zip nat_gateway.js node_modules/
        - aws s3 cp nat_gateway.zip s3://mybucket/lambda/nat_gateway.zip

and you can even use placeholders:

.. code-block:: yaml
  :emphasize-lines: 12,13,14,15,16

    blueprints:
      - stackname: 'my-lambda-function'
        template: lambda.template
        Capabilities: CAPABILITY_IAM
        vars:
          bucket: mybucket
          key: 'lambda/nat_gateway.zip'
        parameters:
          # these are the input parameters passed to the cfn template that match the upload location in the custom script below
          S3Bucket: '{var:bucket}'
          S3Key: '{var:key}'
        before:
        - cd function
        - npm install aws-sdk
        - zip -r nat_gateway.zip nat_gateway.js node_modules/
        - aws s3 cp nat_gateway.zip s3://{var:bucket}/{var:key}

After
=====

Similar to ``before`` scripts you can define scripts that are being executed after the stack has been deployed. Please note this only work if you're 'observing' the deploying (no if you deployed with '--no-observe' or if you're stopping the process (e.g. CTRL+C) during the deployment.

The ``after`` configuration equals the ``before`` configuration with the addition that you have access to the status in the ``${STATUS}`` variable/ (Special status values in addition to the default ones like 'CREATE\_COMPLETE',... are 'NO\_UPDATES\_PERFORMED' and 'STACK\_GONE')

Example

.. code-block:: yaml
  :emphasize-lines: 5,6

    blueprints:
      - stackname: 'my-static-website'
        description: 'Static website hosted in S3'
        template: 'website.template'
        after:
          - 'if [[ $STATUS =~ ^(UPDATE|CREATE)_COMPLETE|NO_UPDATES_PERFORMED$ ]] ; then aws s3 sync --delete content/ s3://www-tst.aoeplay.net/; fi'


Before and after
================

``before`` or ``after`` are being executed in the base directory of the current blueprint (that's the directory the blueprint's blueprint.yml file is located at). But you can switch directories in your script. The ``${CWD}`` variable holds the current working directory (the project root) in case you want to switch to that.

When a profile is being used (even if the profile is loaded via the ``profiles.yml`` file) the ``AWS_ACCESS_KEY_ID`` and ``AWS_SECRET_ACCESS_KEY`` variables will be set in the script context, so you can safely call the aws cli tool in the same context the blueprint is being deployed.

In addition to that ``${BLUEPRINT}`` will hold the current blueprint's name and ``${STACKNAME}`` the current resulting stack name Also ``${STATUS}`` will hold the last status of the stack that has just been deployed (``after`` scripts only).

You can separate the script lines in an array (that will then be concatenated with ``\n`` before executing:

.. code-block:: yaml
  :emphasize-lines: 4,5,6

    blueprints:
      - stackname: 'my-static-website'
        [...]
        after:
          - 'echo "Line 1"'
          - 'echo "Line 2"'

or you can use the YAML multiline notation:

.. code-block:: yaml
  :emphasize-lines: 4,5,6

    blueprints:
      - stackname: 'my-static-website'
        [...]
        after: |
          echo "Line 1"
          echo "Line 2"