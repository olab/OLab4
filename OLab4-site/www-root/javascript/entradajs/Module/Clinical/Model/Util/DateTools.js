/**
 * DateTools.js
 * @author Scott Gibson
 */

module.exports = class DateTools
{
    static formatDate(input) {
        let tmp = ((input instanceof Date) ? input : new Date(input * 1000));
        return tmp ? tmp.getFullYear() + "-" + (((tmp.getMonth() + 1)>9 ? '' : '0') + (tmp.getMonth() + 1)) + "-" + ((tmp.getDate()>9 ? '' : '0') + tmp.getDate()) : '';
    }

    static formatDateString(input) {
        let tmp = ((input instanceof Date) ? input : new Date(input * 1000));
        return tmp ? tmp.toDateString() : '';
    }

    static formatTime(input) {
        let tmp = ((input instanceof Date) ? input : new Date(input * 1000));
        return tmp ? (tmp.getHours() < 10 ? '0' + tmp.getHours() : tmp.getHours()) + ":" + (tmp.getMinutes() < 10 ? '0' + tmp.getMinutes() : tmp.getMinutes()) : '';
    }

    static dateFromTimestamp(timestamp) {
        return timestamp ? new Date(timestamp * 1000) : null;
    }

    static dateFromStartDate(dateString) {
        let ymd = dateString.split('-', 3);
        if (ymd.length === 3) {
            return new Date(ymd[0], ymd[1]-1, ymd[2], 0, 0, 0, 0);
        } else {
            console.log("bad date (" + dateString + ") in timestampFromStartDate");
            return null;
        }
    }

    static dateFromEndDate(dateString) {
        let ymd = dateString.split('-', 3);
        if (ymd.length === 3) {
            return new Date(ymd[0], ymd[1]-1, ymd[2], 23, 59, 59, 0);
        } else {
            console.log("bad date (" + dateString + ") in timestampFromStartDate");
            return null;
        }
    }

    static timestampFromStartDate(dateString) {
        return dateString ? DateTools.timestampFromDate(DateTools.dateFromStartDate(dateString)) : 0;
    }

    static timestampFromEndDate(dateString) {
        return dateString ? DateTools.timestampFromDate(DateTools.dateFromEndDate(dateString)) : 0;
    }

    static timestampFromDate(dateObject) {
        return dateObject ? dateObject.getTime() / 1000 : 0;
    }
};