#!/bin/bash
#
# Entrada [ http://www.entrada-project.org ]
#
# Entrada is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Entrada is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
#
# @author Organisation: University of British Columbia
# @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
# @copyright Copyright 2017 University of British Columbia. All Rights Reserved.
#
# Make a visualization of your git history!
#

(gource -H >&- 2>&-) || {
    echo Sorry, you must install the gource binary first. >&2
    exit 2
}

(ffmpeg -h >&- 2>&-) || {
    echo Sorry, you must install the ffmpeg binary first. >&2
    exit 2
}

function usage {
    echo "Usage: $(basename $0) <months:int> <seconds-per-day:float>" >&2
    echo "       $(basename $0) <months:int> <seconds-per-day:float> -v (to make a video)" >&2
    exit 1
}

MONTHS=$(awk '/^[0-9]+$/ { print $0 }' <<< "$1")

if [[ -z "${MONTHS}" ]]; then
    usage
fi

SECONDS_PER_DAY=$(awk '/^[0-9\.]+$/ { print $0 }' <<< "$2")

if [[ -z "${SECONDS_PER_DAY}" ]]; then
    usage
fi

if [[ -n "$3" ]] && [[ "$3" != "-v" ]]; then
    usage
fi

MAKE_A_VIDEO="$3"

START_DATE=$(date +%Y-%m-%d -d "-${MONTHS} months")

CMD="gource -800x600 --start-date ${START_DATE} -s ${SECONDS_PER_DAY} -i 5 --disable-auto-rotate --logo $(dirname $0)/gource.png --highlight-users --stop-at-end --hide progress,dirnames,filenames"

if [[ -n "$MAKE_A_VIDEO" ]]; then
    $CMD -o - | ffmpeg -y -r 60 -f image2pipe -vcodec ppm -i - -strict -2 -vcodec libx264 "$(dirname $0)/gource.mp4"
else
    $CMD
fi
