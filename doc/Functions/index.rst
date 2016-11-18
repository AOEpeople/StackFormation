*********
Functions
*********

Fn::FileContent
===============

Before uploading CloudFormation template to the API there's some pre-processing going on: I've introduced a new function "FileContent" that accepts a path to a file. This file will be read, converted into JSON (using ``Fn::Join``). The path is relative to the path of the current CloudFormation template file.

Usage Example:

.. code-block:: json

    [...]
    "UserData": {"Fn::Base64": {"Fn::FileContent":"../scripts/setup.sh"}},
    [...]
    
Fn::FileContentTrimLines
========================

These function are similar to ``Fn::FileContent`` but additional it trim whitespace. This comes in handy when deploying Lambda function where the content can't be larger than 2048kb if you want to directly embed the source code via CloudFormation (instead of deploying a zip file).

Fn::FileContentMinify
========================

These function are similar to ``Fn::FileContent`` but additional it minify the code. This comes in handy when deploying Lambda function where the content can't be larger than 2048kb if you want to directly embed the source code via CloudFormation (instead of deploying a zip file).

Fn::FileContentUnpretty
=======================

This function is the same as ``Fn::FileContent`` expect it will return the resulting JSON without formatting it, which will reduce the file size significantly due to the missing whitespace in the JSON structure (not inside the file content!) This is useful if you're seeing the "...at 'templateBody' failed to satisfy constraint: Member must have length less than or equal to 51200" error message.

Fn::Split
=========

Sometimes you have a dynamic number of array items. ``Fn::Split`` allows you to configure them as a single string and transforms them into an array:

.. code-block:: json

    "Aliases": { "Fn::Split": [",", "www.example.com,cdn.example.com"]}

results in:

.. code-block:: json

    "Aliases": ["www.example.com", "cdn.example.com"]
