**************
Stack policies
**************

Using stack policies
====================

To prevent stack resources from being unintentionally updated or deleted during a stack update you can use `stack policies <http://docs.aws.amazon.com/AWSCloudFormation/latest/UserGuide/protect-stack-resources.html>`__. Stack policies apply only during stack updates and should be used only as a fail-safe mechanism to prevent accidental updates to certain stack resources.

It's suggested to create a stack\_policies directory below the corresponding stack directory:

.. code-block:: yaml

    blueprints/
      stack1/
        stack_policies/
        blueprints.yml
        ...
      stack2/
        stack_policies/
        blueprints.yml
        ...
      ...

You have to tell StackFormation where it could find the stack policy.

Example:

.. code-block:: yaml

    blueprints:
      - stackname: 'my-stack'
        template: 'templates/my-stack.template'
        stackPolicy: 'stack_policies/my-stack.json'
