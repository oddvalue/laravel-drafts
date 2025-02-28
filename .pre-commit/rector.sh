#!/usr/bin/env bash
################################################################################

RED='\033[0;31m'
BACKGROUND_RED='\033[0;41m'
YELLOW='\033[0;33m'
BOLD_YELLOW='\033[1;33m'
NC='\033[0m' # No Color

command_files_to_check="${@:2}"
command_args=$1
command_to_run="./vendor/bin/rector ${command_args} ${command_files_to_check}"

command_result=`eval $command_to_run`

if [[ "$command_result" == *FAIL* ]]; then
    echo "$command_result"
    echo -e "${BACKGROUND_RED} Pint failed ${RED} Pint was unable to fix some issues in your files. \
Please fix the errors and try again.${NC}"
    exit 3
fi

if [[ "$command_result" == *FIXED* ]]; then
    echo -e "${BOLD_YELLOW} Pint fixed some issues in your files.${YELLOW} Please re-stage them and try again.${NC}"
    exit 1
fi

exit 0
