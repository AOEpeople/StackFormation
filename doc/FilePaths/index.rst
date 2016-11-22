**********
File paths
**********

Relative file paths
===================

Please note that all files paths in the ``template`` section of a ``blueprints.yml`` are relative to the current ``blueprints.yml`` file and all files included via ``Fn::FileContent``/ ``Fn:FileContentTrimLines`` or ``Fn:FileContentMinify`` are relative to the CloudFormation template file.

Example:

.. code-block:: text

    blueprints/
      stack1/
        userdata/
          provisioning.sh
        blueprints.yml
        my.template

blueprints.yml:

.. code-block:: yaml

    blueprints:
      - stackname: test
        template: my.template

my.template

.. code-block:: json

    { [...]
      "Ec2Instance": {
        "Type": "AWS::AutoScaling::LaunchConfiguration",
        "Properties": {
          "UserData": {"Fn::Base64": {"Fn::FileContent": "userdata/provisioning.sh"}}
        }
      }
    }
