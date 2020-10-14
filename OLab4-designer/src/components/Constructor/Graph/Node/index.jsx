// @flow
import React, { PureComponent } from 'react';
import * as d3 from 'd3';
import debounce from 'lodash.debounce';

import NodeComponent from './NodeComponent';

import { isString } from '../../../../helpers/dataTypes';

import {
  ACTION_COLLAPSE,
  ACTION_LOCK,
  ACTION_ADD,
  ACTION_RESIZE,
  ACTION_LINK,
  ACTION_SELECT,
  COLLAPSED_HEIGHT,
  DEFAULT_NODE_INDENT,
  ACTION_FOCUS,
  BORDER_SIZE,
} from './config';

import type {
  Node as NodeType,
  IPoint as IPointType,
  INodeProps,
  INodeState,
} from './types';

import { ForeignObject } from './styles';

export class Node extends PureComponent<INodeProps, INodeState> {
  nodeRef: any;

  nodeComponentRef: any;

  constructor(props: INodeProps) {
    super(props);
    this.state = {
      isResizeStart: false,
      x: props.data.x || 0,
      y: props.data.y || 0,
      prevX: props.data.x || 0,
      prevY: props.data.y || 0,
      deltaMouseX: 0,
      deltaMouseY: 0,
    };

    this.nodeRef = React.createRef();
    this.nodeComponentRef = React.createRef();
  }

  static getDerivedStateFromProps(nextProps: INodeProps) {
    return {
      x: nextProps.data.x,
      y: nextProps.data.y,
    };
  }

  componentDidMount() {
    const dragFunction = d3
      .drag()
      .on('start', this.handleDragStart)
      .on('drag', this.handleDragMove)
      .on('end', this.handleDragEnd);

    d3
      .select(this.nodeRef.current)
      .on('wheel', this.stopImmediatePropagation)
      .on('mousedown', this.handleMouseDown)
      .call(dragFunction);
  }

  componentDidUpdate(prevProps: INodeProps) {
    const { isResizeStart } = this.state;
    const {
      isResizingStarted,
      onNodeResizeEnded,
      data: {
        id, width, height, isCollapsed,
      },
    } = this.props;

    const isResizeForbidden = !this.nodeComponentRef.current || isCollapsed || !isResizeStart;
    const isResizeEnded = prevProps.isResizingStarted && !isResizingStarted;

    if (isResizeForbidden) {
      return;
    }

    const { offsetWidth, offsetHeight } = this.nodeComponentRef.current;
    const newWidth = offsetWidth - BORDER_SIZE * 2;
    const newHeight = offsetHeight - BORDER_SIZE;

    if (isResizeEnded) {
      if (newWidth !== width || newHeight !== height) {
        onNodeResizeEnded(id, newWidth, newHeight);
      }
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({ isResizeStart: false });
    }
  }

  startResizing = (): void => {
    const { onNodeResizeStarted } = this.props;
    onNodeResizeStarted();

    this.setState({ isResizeStart: true });
  }

  calculateNewNodePosition = (data: NodeType): IPointType => {
    const { x, y, height } = data;
    const newY = y + height / 2 + DEFAULT_NODE_INDENT;

    return { x, y: newY };
  }

  callElementAction = (action: string) => {
    const {
      data,
      onNodeCollapsed,
      onNodeLocked,
      onNodeLink,
      onNodeSelected,
      onNodeFocused,
      onCreateNodeWithEdge,
    } = this.props;

    switch (action) {
      case ACTION_FOCUS:
        onNodeFocused(data.id);
        break;
      case ACTION_LOCK:
        onNodeLocked(data.id);
        break;
      case ACTION_COLLAPSE:
        onNodeCollapsed(data.id);
        break;
      case ACTION_ADD: {
        const { x, y } = this.calculateNewNodePosition(data);
        onCreateNodeWithEdge(x, y, data);
      } break;
      case ACTION_RESIZE:
        this.startResizing();
        break;
      case ACTION_LINK:
        onNodeLink(data);
        break;
      case ACTION_SELECT:
        onNodeSelected(data);
        break;
      default: break;
    }
  }

  getClickedItemAction = () => {
    const { isLinkingStarted } = this.props;
    const { sourceEvent: sourceEventD3, target: targetD3, shiftKey } = d3.event;
    const target = sourceEventD3 ? sourceEventD3.target : targetD3;
    const activeElement = target.closest('[data-active="true"]');

    if (!activeElement) {
      return null;
    }

    const action = activeElement.getAttribute('data-action');
    const shouldStopPropagation = !shiftKey
      && !isLinkingStarted
      && (!action || action !== ACTION_SELECT);

    if (shouldStopPropagation) {
      this.stopImmediatePropagation();
    }

    return action;
  }

  handleMouseDown = () => {
    const { data: { isLocked }, isLinkingStarted } = this.props;
    const { shiftKey } = d3.event;
    const action = this.getClickedItemAction();
    const isActionClickedAndAvailable = (!isLocked && action)
      || (isLocked && action === ACTION_LOCK);
    const isLinking = (shiftKey && action === ACTION_FOCUS)
      || (isLinkingStarted && action === ACTION_FOCUS);

    if (isActionClickedAndAvailable && !isLinking) {
      this.callElementAction(action);
    }
  }

  handleDragMove = () => {
    const { deltaMouseX, deltaMouseY } = this.state;
    const {
      data: { id: dataId, isLocked }, layoutEngine, onNodeMove, isLinkingStarted,
    } = this.props;
    const { offsetWidth, offsetHeight } = this.nodeComponentRef.current;
    const { x, y } = d3.event;
    const { buttons, shiftKey } = d3.event.sourceEvent;
    const mouseButtonDown = buttons === 1;

    if (!mouseButtonDown || (isLocked && !shiftKey) || isLinkingStarted) {
      return;
    }

    const position = {
      x: x - deltaMouseX + offsetWidth / 2,
      y: y - deltaMouseY + offsetHeight / 2,
    };

    if (layoutEngine) {
      Object.assign(position, layoutEngine.calculatePosition(position));
    }

    onNodeMove(position, dataId);

    debounce(() => this.setState(position), 200, {
      trailing: true,
    });
  }

  handleDragStart = () => {
    const { x: prevX, y: prevY } = this.state;
    const {
      data,
      data: { isLocked },
      isLinkSource,
      isLinkingStarted,
      onNodeLink,
    } = this.props;
    const { shiftKey, offsetX: deltaMouseX, offsetY: deltaMouseY } = d3.event.sourceEvent;
    const { current: currentNodeRef } = this.nodeRef;
    const { parentElement: currentNodeRefParent } = currentNodeRef;

    this.setState({
      prevX,
      prevY,
      deltaMouseX,
      deltaMouseY,
    });

    if (shiftKey && !isLinkSource) {
      onNodeLink(data);
    }

    if (!currentNodeRef || isLocked || isLinkingStarted) {
      return;
    }

    // Moves child to the end of the element stack to re-arrange the z-index
    currentNodeRefParent.parentElement.appendChild(currentNodeRefParent);
  }

  handleDragEnd = () => {
    const {
      x, y, prevX, prevY,
    } = this.state;
    const { data, onNodeUpdate, isLinkingStarted } = this.props;
    const { current: currentNodeRef } = this.nodeRef;

    if (!currentNodeRef) {
      return;
    }

    const action = this.getClickedItemAction();

    const isInappropriateAction = !action || action === ACTION_RESIZE || action === ACTION_SELECT;
    const shouldUpdateNode = ((!isLinkingStarted && action !== ACTION_FOCUS)
      && (prevX !== x || prevY !== y))
      || (isLinkingStarted && isInappropriateAction);

    if (shouldUpdateNode) {
      onNodeUpdate({ x, y }, data.id);
    }
  }

  stopImmediatePropagation = () => {
    if (d3.event.stopImmediatePropagation) {
      d3.event.stopImmediatePropagation();
    }
  }

  renderShape() {
    const {
      isLinkSource,
      data: {
        id,
        text,
        type,
        title,
        color,
        width: currentWidth,
        height,
        isCollapsed,
        isLocked,
        isSelected,
        isEnd,
      },
    } = this.props;

    const currentHeight = isCollapsed ? COLLAPSED_HEIGHT : height;

    return (
      <ForeignObject
        x={-currentWidth / 2}
        y={-currentHeight / 2}
        width={currentWidth}
        height={currentHeight}
        viewBox={`0 0 ${currentWidth} ${currentHeight}`}
        isDisabled={isString(id)}
      >
        <NodeComponent
          isLinked={isLinkSource}
          isLocked={isLocked}
          isCollapsed={isCollapsed}
          isSelected={isSelected}
          nodeComponentRef={this.nodeComponentRef}
          width={currentWidth}
          height={height}
          type={type}
          title={title}
          text={text}
          color={color}
          isEnd={isEnd}
        />
      </ForeignObject>
    );
  }

  render() {
    const { x, y } = this.state;
    const { id } = this.props;

    return (
      <g
        id={id}
        ref={this.nodeRef}
        transform={`translate(${x}, ${y})`}
      >
        {this.renderShape()}
      </g>
    );
  }
}

export default Node;
