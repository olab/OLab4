function CreateScrollBar(content) {
    //get dimensions for scrollbar content and container
    this.content = content;
    this.container = getContainer(content);
    this.windowElementHeight = this.container.clientHeight;
    this.contentHeight = this.content.scrollHeight;
    this.scrollAreaSize = this.contentHeight - this.windowElementHeight;
    this.windowPosition = this.content.scrollTop;
    this.windowPositionRatio = this.windowPosition / this.scrollAreaSize;


    //create and add scrollbar this.track
    this.track = document.createElement('div');
    this.track.className = "track";
    this.container.appendChild(this.track);


    //get dimensions for scrollbar elements
    this.gripSize = this.track.clientHeight * this.windowContentRatio();
    this.minGripSize = 40;
    this.maxGripSize = this.track.clientHeight;
    if (this.gripSize < this.minGripSize) {
        this.gripSize = this.minGripSize;
    } else if (this.gripSize > this.maxGripSize) {
        this.gripSize = this.maxGripSize;
    }
    this.trackScrollAreaSize = this.track.clientHeight - this.gripSize;

    //create grip and add it
    this.grip = document.createElement('div');
    this.grip.className = "grip";
    this.track.appendChild(this.grip);
    this.grip.style.height = this.gripSize + "px";
    this.grip.style.top = this.gripPosition + "px";
    var _this = this;

    //add mouse listener
    mouseDrag(this.track, this.content);
    this.content.addEventListener('scroll', this.moveBar.bind(this));

    //Mouse Event Handler
    function mouseDrag(track, content) {
            var lastPageY;
            track.addEventListener('mousedown', function(e) {
                if (e.target.classList.value === "track") {
                    //track is grabbed
                    var trackPos = e.target.getBoundingClientRect();
                    var gripPos = e.target.querySelector(".grip").getBoundingClientRect();
                    if (gripPos.top > e.clientY) {
                        // scroll up
                        content.scrollTop -= 40;
                    } else {
                        // scroll down
                        content.scrollTop += 40;
                    }
                }
                lastPageY = e.pageY;
                track.classList.add("grabbed");
                document.body.classList.add("grabbed"); //prevent selection while dragging css{}
                document.addEventListener("mousemove", drag);
                document.addEventListener("mouseup", stop);
                return false;
            });

            function drag(e) {
                window.requestAnimationFrame(function() {
                    var delta = e.pageY - lastPageY;
                    lastPageY = e.pageY;
                    content.scrollTop += delta / _this.windowContentRatio();
                });
            }

            function stop() {
                //remove grab event
                track.classList.remove('grabbed');
                document.body.classList.remove('grabbed');
                document.removeEventListener('mousemove', drag);
                document.removeEventListener('mouseup', stop);
            }
        } //end mouseDrag

}

function getContainer(el) {
    var p;
    while (el) {
        p = el.parentElement;
        if ((" " + p.className + " ").indexOf("timeline-container") > -1) {
            return p;
        }
        el = p;
    }
}

CreateScrollBar.prototype = {
    windowContentRatio: function() {
        return this.windowElementHeight / this.contentHeight;
    },
    moveBar: function(e) {
        this.grip.style.top = this.gripPosition() + "px";
    },
    gripPosition: function() {
        this.windowPositionRatio = this.content.scrollTop / this.scrollAreaSize;
        var gripPos = this.trackScrollAreaSize * this.windowPositionRatio;
        if (gripPos + this.gripSize > this.track.clientHeight) {
            gripPos = this.trackScrollAreaSize;
        }
        return gripPos;
    }
};