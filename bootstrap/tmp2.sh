#!/bin/bash

for i in {1..30}
do
   cd $i
   sh ../tmp.sh
   cd ..
done