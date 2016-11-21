*****
Stack
*****

Stackname filter
================

You can configure a regular expression in the ``STACKFORMATION_NAME_FILTER`` environment variable (e.g. via ``.env.default``) which will filter all your stack lists to the stacks matching this pattern. This is useful if you have a naming convention in place and you don't want to see other team's stacks in your list.

Example:

.. code-block:: text

    STACKFORMATION_NAME_FILTER=/^myproject-(a|b)-/
