/**
 * DateInterval.js
 * @author Scott Gibson
 */

module.exports = class DateInterval
{
    constructor(date1, date2) {
        if(date1 < date2) {
            this.date1 = date1;
            this.date2 = date2;
        }
        else {
            this.date1 = date2;
            this.date2 = date1;
        }

        let milliseconds = this.date2.getTime() - this.date1.getTime();

        this.timezoneOffset = (this.date1.getTimezoneOffset() - this.date2.getTimezoneOffset()) * 60 * 1000;
        this.milliseconds = milliseconds + this.timezoneOffset;

        let descriptor = {
            configurable: false,
            enumerable: true,
            writable: false
        };

        Object.defineProperties(this, {
            date1: descriptor,
            date2: descriptor,
            milliseconds: descriptor
        });
    }

    toValue() {
        return this.milliseconds;
    }

    daysInMonth(year, monthIndex) {
        return new Date(year, monthIndex + 1, 0).getDate();
    }

    averageMonthLength(startDate, endDate) {
        let monthDays = [];

        let workingDate = new Date(startDate);

        while(workingDate < endDate) {
            monthDays.push(this.daysInMonth(workingDate.getFullYear(), workingDate.getMonth()));

            workingDate.setMonth(workingDate.getMonth() + 1);
        }

        if(monthDays.length === 0) {
            return 0;
        }

        let sum = monthDays.reduce((carry, value) => carry + value, 0);

        return Math.round(sum) / monthDays.length;
    }

    get seconds() {
        return this.milliseconds / 1000;
    }

    get minutes() {
        return this.seconds / 60;
    }

    get hours() {
        return this.minutes / 60;
    }

    get days() {
        return this.hours / 24;
    }

    get weeks() {
        return this.days / 7;
    }

    get months() {
        return Math.round(this.days) / this.averageMonthLength(this.date1, this.date2);
    }

    get years() {
        return this.date2.getFullYear() - this.date1.getFullYear();
    }
};