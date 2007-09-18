#!/bin/bash

for i in `seq 1 10`;
do
	time ./regressions.sh --profile &> /dev/null
done
