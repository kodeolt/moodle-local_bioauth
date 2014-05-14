BioAuth
====================
Behavioral Biometric Authentication plugin for Moodle

Created and maintained by Vinnie Monaco

# Purpose
The BioAuth Moodle Plugin records keyboard and mouse activity site-wide on Moodle. On every page which contains site navigation elements, a script is loaded on the client that periodically sends keystroke and mouse events back to the server. The data can then be downloaded and used for behavioral biometric authentication or identification.

# Installation
To install using git, type these commands in the root of your Moodle installation
    git clone https://vmonaco@bitbucket.org/vmonaco/moodle-local_bioauth.git local/bioauth
    
Remember to add /local/bioauth the .gitignore of your Moodle directory if it is version controlled.

