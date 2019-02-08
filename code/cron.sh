#!/bin/bash

# read in all the links as separate lines
data=`jq -c ".[]" links.json`

triggers=`ls -d events/*`
actions=`ls -d actions/*`

while IFS= read -r line ; do
    # now for each line call the trigger to find out if we should call the event
    event=`echo $line | jq -r ".event"`
    action=`echo $line | jq -r ".action"`
    user=`echo $line | jq -r ".user"`
    id=`echo $line | jq -r ".id"`
    while IFS= read -r line2 ; do
        name=`cat ${line2}/info.json | jq -r ".name"`
        if [ "$name" == "$event" ]; then
            script=${line2}/`cat ${line2}/info.json | jq -r ".script"`
            # echo "trigger is defined in ${line2}/info.json, call script \"${script}\" with ${line}"
            ret=`eval "${script} '${line}'"`
            if [ "$?" -eq "1" ]; then
                #echo "action will be performed"
                # what action?
                while IFS= read -r line3 ; do
                    name=`cat ${line3}/info.json | jq -r ".name"`
                    if [ "$name" == "$action" ]; then
                        script2=${line3}/`cat ${line3}/info.json | jq -r ".script"`
                        echo "`date`: action is defined in ${line3}/info.json, call script \"${script2}\" with ${line}"
                        ret2=`eval "${script2} '${line}'" 2>> ${script2}_log`
                        echo "${ret2}"
                        # The output ret2 should now be send as an email to the current user
                        echo ${ret2} | ./sendAsEmail.sh "${user}" "${action} (${id})" -
                    fi
                done <<< "$actions"
            else
                echo "action should not be done for trigger (${script}): $ret"
            fi
            #echo "trigger returned: $ret"
        fi
    done <<< "$triggers"
done <<< "$data"
