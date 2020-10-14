/*
 * @see {@link spec} It describes how the drop target reacts to the drag and drop events.
 * @see {@link http://react-dnd.github.io/react-dnd/docs/api/drag-source#parameters|React-DND}
*/
export const spec = {
  beginDrag: props => props,
  endDrag: (props, monitor, component) => {
    if (!monitor.didDrop()) {
      return;
    }

    const dropResult = monitor.getDropResult();

    if (!dropResult) {
      return;
    }

    const { x: offsetX, y: offsetY } = dropResult;
    const { handleModalMove } = component;

    handleModalMove(offsetX, offsetY);
  },
};

/*
  * @see {@link collect} It should return a plain object of the props to inject into your component.
  * @see {@link collect} It receives two parameters: connect and monitor.
  * @see {@link http://react-dnd.github.io/react-dnd/docs/api/drag-source#parameters|React-DND}
*/
export const collect = (conn, monitor) => ({
  connectDragSource: conn.dragSource(),
  connectDragPreview: conn.dragPreview(),
  isDragging: monitor.isDragging(),
});
