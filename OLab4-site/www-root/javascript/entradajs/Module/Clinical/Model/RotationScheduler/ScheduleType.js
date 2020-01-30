/**
 * ScheduleType.js
 * @author Scott Gibson
 */

module.exports = class ScheduleType
{
    static get Block() {
        return 'block';
    }

    static get Stream() {
        return 'stream';
    }

    static get RotationBlock() {
        return 'rotation_block';
    }

    static get RotationStream() {
        return 'rotation_stream';
    }
};