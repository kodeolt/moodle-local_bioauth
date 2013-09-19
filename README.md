BioAuth
====================
Behavioral Biometric Authentication plugin for Moodle

Created and maintained by Vinnie Monaco

# Purpose
The BioAuth Moodle Plugin utilizes behavioral biometrics recorded during quiz attempts in order to verify the identity of online test takers. Instructors are able to confirm or dismiss suspicions of cheating students and respond appropriately. The workflow of existing courses will not be affected, allowing a seamless integration for institutions who wish to adopt this technology and ensure academic integrity.

# Installation
To install using git, type these commands in the root of your Moodle install
    git clone git@github.com:vmonaco/moodle-local_bioauth.git local/bioauth
    git clone git@github.com:vmonaco/moodle-quizaccess_biologger.git mod/quiz/accessrule/biologger
    
Then add /local/bioauth and mod/quiz/accessrule/biologger to your git ignore.

The local plugin contains everything needed to perform biometric authentication. The quiz access-rule plugin allows behavioral biometric data to be collected during quiz attempts.

After you have installed the plugin, you will be able to view authentication reports in the sidebare under:
Bioauth -> Report

