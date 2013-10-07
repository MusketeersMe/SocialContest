#!/bin/bash
nohup php incoming.php > ./log/incoming.log &
nohup php to_approve.php > ./log/to_approve.log &
nohup php to_denied.php > ./log/to_denied.log &
nohup php to_incoming.php > ./log/to_incoming.log &
nohup php to_winner.php > ./log/to_winner.log &
