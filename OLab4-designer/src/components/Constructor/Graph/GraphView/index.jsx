// @flow
/* eslint-disable react/no-unused-state */
/*
The top-level component digraph component.
Here it is possible to manipilate with nodes and edges.
*/
import * as d3 from 'd3';
import React from 'react';
import isEqual from 'lodash.isequal';
import omit from 'lodash.omit';
import { connect } from 'react-redux';
import ReactDOM from 'react-dom';

import Node from '../Node';
import Edge from '../Edge';
import Defs from '../Defs';
import Background from '../Background';
import ZoomControls from '../ZoomControls';

import {
  CURSOR_DEFAULT, CURSOR_CUSTOM_CROSSHAIR, ZOOM_CONTROLS_ID,
} from '../../config';

import * as constructorActions from '../../../../redux/constructor/action';

import {
  getEdgePathElement, parsePathToXY, calculateOffset,
} from '../Edge/utils';
import {
  removeEdgeElement, isPartOfEdge, zoomFilter, getClickedDOMNodeId, yieldingLoop,
  removeElementFromDom, linkNodesAndEdges, getEdgesMap, getNodesMap,
} from './utils';
import LayoutEngines from '../utilities/layout-engine/layout-engine-config';

import type { Edge as EdgeType } from '../Edge/types';
import type { IPoint, Node as NodeType } from '../Node/types';
import type { INodeMapNode, IGraphViewState, IGraphViewProps } from './types';

import { View, ViewWrapper, GraphWrapper } from './styles';

export class GraphView extends React.Component<IGraphViewProps, IGraphViewState> {
  constructor(props: IGraphViewProps) {
    super(props);

    this.nodeTimeouts = {};
    this.edgeTimeouts = {};
    this.renderNodesTimeout = null;
    this.renderEdgesTimeout = null;
    this.viewWrapper = React.createRef();
    this.graphSvg = React.createRef();

    if (props.layoutEngine) {
      this.layoutEngine = new LayoutEngines[props.layoutEngine](props);
    }

    this.state = {
      edges: [],
      edgesMap: {},
      nodes: [],
      nodesMap: {},
      draggedEdge: null,
      draggingEdge: false,
      selectedEdgeObj: null,
      selectedNodeObj: null,
      sourceNode: null,
      focused: true,
      svgClicked: false,
      documentClicked: false,
      componentUpToDate: false,
      isLinkingStarted: false,
      isResizingStarted: false,
    };
  }

  static defaultProps = {
    edgeArrowSize: 6,
    gridSpacing: 36,
    maxZoom: 1.5,
    minZoom: 0.15,
    readOnly: false,
    zoomDelay: 1000,
    zoomDur: 750,
  };

  static getDerivedStateFromProps(nextProps: IGraphViewProps, prevState: IGraphViewState) {
    let { nodes } = nextProps;
    const { edges } = nextProps;
    const nodesMap = getNodesMap(nodes);
    const edgesMap = getEdgesMap(edges);
    linkNodesAndEdges(nodesMap, edges);

    const selectedNodeMap = nextProps.selected && nodesMap[`key-${nextProps.selected.id}`]
      ? nodesMap[`key-${nextProps.selected.id}`]
      : null;
    const selectedEdgeMap = nextProps.selected && edgesMap[`${nextProps.selected.source}_${nextProps.selected.target}`]
      ? edgesMap[`${nextProps.selected.source}_${nextProps.selected.target}`]
      : null;

    // Handle layoutEngine on initial render
    if (prevState.nodes.length === 0
        && nextProps.layoutEngine
        && LayoutEngines[nextProps.layoutEngine]
    ) {
      const layoutEngine = new LayoutEngines[nextProps.layoutEngine](nextProps);
      nodes = layoutEngine.adjustNodes(nodes, nodesMap);
    }

    const nextState = {
      componentUpToDate: true,
      edges,
      edgesMap,
      nodes,
      nodesMap,
      readOnly: nextProps.readOnly,
      selectedEdgeObj: {
        edge: selectedEdgeMap ? selectedEdgeMap.edge : null,
      },
      selectedNodeObj: {
        nodeId: selectedNodeMap ? nextProps.selected.id : null,
        node: selectedNodeMap ? selectedNodeMap.node : null,
      },
      selectionChanged: false,
    };

    return nextState;
  }

  componentDidMount() {
    const { minZoom = 0, maxZoom = 0, zoomDelay } = this.props;

    // TODO: can we target the element rather than the document?
    document.addEventListener('keydown', this.handleWrapperKeydown);
    document.addEventListener('click', this.handleDocumentClick, true);

    this.zoom = d3
      .zoom()
      .filter(zoomFilter)
      .scaleExtent([minZoom, maxZoom])
      .on('start', this.handleZoomStart)
      .on('zoom', this.handleZoom)
      .on('end', this.handleZoomEnd);

    d3
      .select(this.viewWrapper.current)
      .on('click', this.handleSvgClicked) // handle element click in the element components
      .on('mouseup', this.stopResizing)
      .select('svg')
      .on('zoom.dbclick', null)
      .call(this.zoom);
    this.selectedView = d3.select(this.view);

    // On the initial load, the 'view' <g> doesn't exist until componentDidMount.
    // Manually render the first view.
    this.renderView();
    this.asyncHandleZoomToFit(zoomDelay);
  }

  shouldComponentUpdate(nextProps: IGraphViewProps, nextState: IGraphViewState) {
    const { sourceNode, isLinkingStarted, isResizingStarted } = this.state;
    const {
      nodes, edges, selected, readOnly, layoutEngine, cursor,
    } = this.props;

    if (nextProps.nodes !== nodes
      || nextProps.edges !== edges
      || !nextState.componentUpToDate
      || nextProps.selected !== selected
      || nextProps.readOnly !== readOnly
      || nextProps.layoutEngine !== layoutEngine
      || nextState.isLinkingStarted !== isLinkingStarted
      || nextState.isResizingStarted !== isResizingStarted
      || nextState.sourceNode !== sourceNode
      || nextProps.cursor !== cursor
    ) {
      return true;
    }

    return false;
  }

  componentDidUpdate(prevProps: IGraphViewProps, prevState: IGraphViewState) {
    const {
      nodesMap,
      edgesMap,
      nodes: stateNodes,
      edges: stateEdges,
      selectedNodeObj,
      selectedEdgeObj,
      componentUpToDate,
      sourceNode,
      isLinkingStarted,
      isResizingStarted,
    } = this.state;
    const {
      edges: propsEdges,
      nodes: propsNodes,
      layoutEngine,
      ACTION_SET_CURSOR,
    } = this.props;

    if (!isLinkingStarted && prevState.isLinkingStarted) {
      d3
        .select(this.viewWrapper.current)
        .on('mousemove', null);

      ACTION_SET_CURSOR(CURSOR_DEFAULT);

      removeElementFromDom('edge-custom-container');
    } else if (isLinkingStarted && !prevState.isLinkingStarted) {
      d3
        .select(this.viewWrapper.current)
        .on('mousemove', this.handleMouseMove);

      ACTION_SET_CURSOR(CURSOR_CUSTOM_CROSSHAIR);
    }

    if (layoutEngine && LayoutEngines[layoutEngine]) {
      this.layoutEngine = new LayoutEngines[layoutEngine](this.props);

      const newNodes = this.layoutEngine.adjustNodes(stateNodes, nodesMap);
      if (!isEqual(stateNodes, newNodes)) {
        // eslint-disable-next-line react/no-did-update-set-state
        this.setState({
          nodes: newNodes,
        });
      }
    }

    const forceReRender = propsNodes !== prevProps.nodes
      || propsEdges !== prevProps.edges
      || !componentUpToDate
      || sourceNode !== prevState.sourceNode
      || layoutEngine !== prevProps.layoutEngine
      || isLinkingStarted !== prevState.isLinkingStarted
      || isResizingStarted !== prevState.isResizingStarted;
    const shouldZoomToFit = propsEdges.length !== prevProps.edges.length
      || propsNodes.length !== prevProps.nodes.length
      || layoutEngine !== prevProps.layoutEngine;

    // Note: the order is intentional
    // remove old edges
    this.removeOldEdges(prevState.edges, edgesMap);

    // remove old nodes
    this.removeOldNodes(prevState.nodes, prevState.nodesMap, nodesMap);

    // add new nodes
    this.addNewNodes(
      stateNodes,
      prevState.nodesMap,
      selectedNodeObj,
      prevState.selectedNodeObj,
      forceReRender,
    );

    // add new edges
    this.addNewEdges(
      stateEdges,
      prevState.edgesMap,
      selectedEdgeObj,
      prevState.selectedEdgeObj,
      forceReRender,
    );

    if (shouldZoomToFit) {
      this.asyncHandleZoomToFit(50);
    }

    if (!componentUpToDate) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({
        componentUpToDate: true,
      });
    }
  }

  componentWillUnmount() {
    document.removeEventListener('keydown', this.handleWrapperKeydown);
    document.removeEventListener('click', this.handleDocumentClick, true);
  }

  stopResizing = (): void => {
    this.setState({ isResizingStarted: false });
  }

  startResizing = (): void => {
    this.setState({ isResizingStarted: true });
  }

  asyncHandleZoomToFit = (delay: number = 0): void => {
    setTimeout(() => {
      if (this.viewWrapper != null && this.entities) {
        this.handleZoomToFit();
      }
    }, delay);
  }

  handleMouseMove = () => {
    const { sourceNode } = this.state;
    const [x, y] = d3.mouse(this.view);

    this.asyncRenderEdge({
      source: sourceNode.id,
      targetPosition: { x, y },
    });
  }

  toggleDraggingEdgeByIcon = (newSourceNode?: NodeType) => {
    const { isLinkingStarted, sourceNode } = this.state;
    const isNodesEqual = isEqual(sourceNode, newSourceNode);

    if (!newSourceNode || isNodesEqual) {
      const toggledSourceNode = isLinkingStarted ? null : newSourceNode;

      this.setState({
        isLinkingStarted: !isLinkingStarted,
        sourceNode: toggledSourceNode,
      });
    } else {
      this.setState({
        isLinkingStarted: true,
        sourceNode: newSourceNode,
      });
    }
  }

  getNodeById(id: string | number | null, nodesMap: any | null): INodeMapNode | null {
    const { nodesMap: stateNodesMap } = this.state;
    const nodesMapVar = nodesMap || stateNodesMap;

    return nodesMapVar ? nodesMapVar[`key-${id || ''}`] : null;
  }

  getEdgeBySourceTarget(source: string, target: string): EdgeType | null {
    const { edgesMap } = this.state;

    return edgesMap ? edgesMap[`${source}_${target}`] : null;
  }

  addNewNodes(
    nodes: Array<NodeType>,
    oldNodesMap: any,
    selectedNode: any,
    prevSelectedNode: any,
    forceRender: boolean = false,
  ) {
    const { draggingEdge } = this.state;

    if (draggingEdge) {
      return;
    }

    let node = null;
    let prevNode = null;

    yieldingLoop(nodes.length, 50, (i: number) => {
      node = nodes[i];
      prevNode = this.getNodeById(node.id, oldNodesMap);

      // if there was a previous node and it changed
      if (prevNode != null
        && (prevNode.node !== node
          || (selectedNode.node !== prevSelectedNode.node
            && ((selectedNode.node && node.id === selectedNode.node.id)
              || (prevSelectedNode.node && node.id === prevSelectedNode.node.id))))) {
        // Updated node
        this.syncRenderNode(node);
      } else if (forceRender || !prevNode) {
        // New node
        this.syncRenderNode(node);
      }
    });
  }

  removeOldNodes(prevNodes: any, prevNodesMap: any, nodesMap: any) {
    // remove old nodes
    prevNodes.forEach((prevNode) => {
      const nodeId = prevNode.id;

      // Check for deletions
      if (!this.getNodeById(nodeId, nodesMap)) {
        const prevNodeMapNode = this.getNodeById(nodeId, prevNodesMap);
        // remove all outgoing edges
        prevNodeMapNode.outgoingEdges.forEach((edge) => {
          removeEdgeElement(edge.source, edge.target);
        });

        // remove all incoming edges
        prevNodeMapNode.incomingEdges.forEach((edge) => {
          removeEdgeElement(edge.source, edge.target);
        });

        // remove node
        // The timeout avoids a race condition
        setTimeout(() => {
          removeElementFromDom(`node-${nodeId}-container`);
        });
      }
    });
  }

  addNewEdges(
    edges: Array<EdgeType>,
    oldEdgesMap: any,
    selectedEdge: any,
    prevSelectedEdge: any,
    forceRender: boolean = false,
  ) {
    const { draggingEdge } = this.state;

    if (!draggingEdge) {
      let edge = null;

      for (let i = 0; i < edges.length; i += 1) {
        edge = edges[i];
        if (!edge.source || !edge.target) {
          // eslint-disable-next-line no-continue
          continue;
        }

        const prevEdge = oldEdgesMap[`${edge.source}_${edge.target}`];
        if (forceRender || !prevEdge
            || ( // selection change
              selectedEdge !== prevSelectedEdge
              && (
                (selectedEdge.edge && edge === selectedEdge.edge)
                  || (prevSelectedEdge.edge && prevSelectedEdge.edge)
              )
            )
        ) {
          // new edge
          this.syncRenderEdge(edge, false);
        }
      }
    }
  }

  removeOldEdges = (prevEdges: Array<EdgeType>, edgesMap: any) => {
    // remove old edges
    prevEdges.forEach((edge: EdgeType) => {
      if (!edge.source || !edge.target || !edgesMap[`${edge.source}_${edge.target}`]) {
        // remove edge
        removeEdgeElement(edge.source, edge.target);
      }
    });
  }

  deleteNode(selectedNode: NodeType) {
    const { onDeleteNode } = this.props;

    onDeleteNode(selectedNode);

    this.setState({
      componentUpToDate: false,
    });
  }

  deleteEdge(selectedEdge: EdgeType) {
    const { edges, edgesMap } = this.state;
    const { onDeleteEdge } = this.props;

    if (!selectedEdge.source || !selectedEdge.target) {
      return;
    }

    const newEdgesArr = edges.filter(edge => !(
      edge.source === selectedEdge.source
        && edge.target === selectedEdge.target
    ));

    const newEdgesMap = omit(
      edgesMap,
      `${selectedEdge.source}_${selectedEdge.target}`,
    );

    onDeleteEdge(selectedEdge);

    this.setState({
      componentUpToDate: false,
      edges: newEdgesArr,
      edgesMap: newEdgesMap,
    });
  }

  handleDelete = (selected: EdgeType | NodeType) => {
    const { readOnly } = this.props;
    const isEdge = !!selected.source;

    if (readOnly || !selected) {
      return;
    }

    if (isEdge) {
      this.deleteEdge(selected);
    } else {
      this.deleteNode(selected);
    }
  }

  handleWrapperKeydown: KeyboardEventListener = (d) => {
    const {
      selectedNodeObj, isLinkingStarted, focused,
    } = this.state;
    const {
      onUndo, onRedo, onCopySelected, onPasteSelected, selected,
    } = this.props;

    if (!focused) {
      return;
    }

    const isCtrlKeyPressed = d.metaKey || d.ctrlKey;

    switch (d.key) {
      case 'Delete':
      case 'Backspace': {
        const isMapItemSelected = (selectedNodeObj && selectedNodeObj.node) || selected;

        if (isMapItemSelected) {
          this.handleDelete(selectedNodeObj.node || selected);
        }
      } break;
      case 'Escape':
        if (isLinkingStarted) {
          this.toggleDraggingEdgeByIcon();
        }
        break;
      case 'z':
        if (isCtrlKeyPressed && onUndo) {
          onUndo();
        }
        break;
      case 'y':
        if (isCtrlKeyPressed && onRedo) {
          onRedo();
        }
        break;
      case 'c':
        if (isCtrlKeyPressed && selectedNodeObj.node && onCopySelected) {
          onCopySelected();
        }
        break;
      case 'v':
        if (isCtrlKeyPressed && selectedNodeObj.node && onPasteSelected) {
          onPasteSelected();
        }
        break;
      default:
        break;
    }
  }

  handleEdgeSelected = (e: Event) => {
    const { edges } = this.state;
    const { onSelectEdge } = this.props;
    const { source, target } = e.target.dataset;
    const { clientX, clientY } = d3.event;

    const newState = {
      svgClicked: true,
      focused: true,
    };

    if (source && target) {
      const edge: EdgeType | null = this.getEdgeBySourceTarget(source, target);

      Object.assign(newState, {
        selectedEdgeObj: {
          componentUpToDate: false,
          edge: edges[edge.originalArrIndex],
        },
      });

      onSelectEdge(edges[edge.originalArrIndex], clientX, clientY);
    }

    this.setState(newState);
  }

  handleSvgClicked = () => {
    const { event } = d3;
    const { target, shiftKey } = event;
    const { selectedNodeObj, isLinkingStarted } = this.state;

    const nodeDOMId = getClickedDOMNodeId();

    if (isLinkingStarted && !nodeDOMId) {
      this.setState({
        isLinkingStarted: false,
        sourceNode: null,
      });
    }

    if (isPartOfEdge(target)) {
      this.handleEdgeSelected(event);

      return;
    }

    const { readOnly, onCreateNode, onSelectNode } = this.props;
    const previousSelection = (selectedNodeObj && selectedNodeObj.node) || null;
    const shouldDeselectNode = !readOnly && !isLinkingStarted && !nodeDOMId;
    const shouldCreateNode = !readOnly && !isLinkingStarted && shiftKey;

    if (previousSelection) {
      this.syncRenderNode(previousSelection);
    }

    if (shouldDeselectNode) {
      onSelectNode(null);
    }

    if (shouldCreateNode) {
      const [x, y] = d3.mouse(target);
      onCreateNode(x, y);
    }

    this.setState({
      selectedNodeObj: null,
      focused: true,
      svgClicked: true,
    });
  }

  handleDocumentClick = (e: MouseEvent) => {
    // Ignore document click if it's in the SVGElement
    const { selected: isItemSelected, focused: isNodeFocused } = this.props;
    const isTargetEqualsToViewport = e && e.target && this.graphSvg.current.contains(e.target);
    const shouldLeaveFocus = !isItemSelected && (isTargetEqualsToViewport || !isNodeFocused);

    if (shouldLeaveFocus || isTargetEqualsToViewport) {
      return;
    }

    this.setState({
      documentClicked: true,
      focused: false,
      svgClicked: false,
      isLinkingStarted: false,
      sourceNode: null,
    });
  }

  handleNodeMove = (position: IPoint, nodeId: number) => {
    const { readOnly } = this.props;
    const nodeMapNode = this.getNodeById(nodeId);

    if (readOnly || !nodeMapNode) {
      return;
    }

    // node moved
    nodeMapNode.node.x = position.x;
    nodeMapNode.node.y = position.y;

    // Update edges for node
    this.renderConnectedEdgesFromNode(nodeMapNode, true);
    this.syncRenderNode(nodeMapNode.node);
  }

  createNewEdge() {
    const { edgesMap, sourceNode } = this.state;
    const { onCreateEdge } = this.props;
    const nodeDOMId = getClickedDOMNodeId();
    const newState = {
      draggedEdge: null,
      draggingEdge: false,
    };

    if (nodeDOMId) {
      const { node: targetNode } = this.getNodeById(nodeDOMId);
      const mapId = `${sourceNode.id}_${targetNode.id}`;
      const isDrawNewEdge = edgesMap && sourceNode.id !== targetNode.id && !edgesMap[mapId];

      if (!isDrawNewEdge) {
        return;
      }

      const edge = {
        source: sourceNode.id,
        target: targetNode.id,
      };

      Object.assign(newState, {
        componentUpToDate: false,
        isLinkingStarted: false,
        sourceNode: null,
      });

      // this syncRenderEdge will render the edge as un-selected.
      this.syncRenderEdge(edge);
      // we expect the parent website to set the selected property
      // to the new edge when it's created
      onCreateEdge(sourceNode, targetNode);
    }

    removeElementFromDom('edge-custom-container');

    this.setState(newState);
  }

  handleNodeUpdate = (position: any, nodeId: number) => {
    const { isLinkingStarted } = this.state;
    const { onUpdateNode, readOnly } = this.props;

    if (readOnly) {
      return;
    }

    // Detect if edge is being drawn and link to hovered node
    // This will handle a new edge
    if (isLinkingStarted) {
      this.createNewEdge();
    } else {
      const nodeMap = this.getNodeById(nodeId);
      if (nodeMap) {
        Object.assign(nodeMap.node, position);
        onUpdateNode(nodeMap.node);
      }
    }

    // force a re-render
    this.setState({
      componentUpToDate: false,
      focused: true,
      // Setting hoveredNode to false here because the layout engine doesn't
      // fire the mouseLeave event when nodes are moved.
      svgClicked: true,
    });
  }

  handleNodeSelected = (node: NodeType) => {
    const { onSelectNode } = this.props;
    onSelectNode(node);

    this.setState({
      focused: true,
      componentUpToDate: false,
      selectedNodeObj: {
        nodeId: node.id,
        node,
      },
    });
  }

  // One can't attach handlers to 'markers' or obtain them from the event.target
  // If the click occurs within a certain radius of edge target, assume the click
  // occurred on the arrow
  isArrowClicked(edge: EdgeType | null) {
    const { edgeArrowSize = 0 } = this.props;
    const { target: eventTarget } = d3.event.sourceEvent;
    if (!edge || !edge.target || eventTarget.tagName !== 'path') {
      return false; // If the handle is clicked
    }

    const source = d3.mouse(eventTarget);
    const edgeCoords = parsePathToXY(getEdgePathElement(edge, this.viewWrapper.current));

    // the arrow is clicked if the xyCoords are within edgeArrowSize of edgeCoords.target[x,y]
    return (
      source.x < edgeCoords.target.x + edgeArrowSize
      && source.x > edgeCoords.target.x - edgeArrowSize
      && source.y < edgeCoords.target.y + edgeArrowSize
      && source.y > edgeCoords.target.y - edgeArrowSize
    );
  }

  handleZoomStart = () => {
    // Zoom start events also handle edge clicks. We need to determine if an edge
    // was clicked and deal with that scenario.
    const { sourceEvent } = d3.event;
    const { edgesMap } = this.state;
    const { readOnly } = this.props;

    if (
      // graph can't be modified
      readOnly
      // no sourceEvent, not an action on an element
      || !sourceEvent
      // not a click event
      || (sourceEvent && !sourceEvent.buttons)
    ) {
      return;
    }

    // Clicked on the edge.
    const { id: edgeId } = sourceEvent.target;
    const edge = edgesMap && edgesMap[edgeId] ? edgesMap[edgeId].edge : null;

    // Only move edges if the arrow is dragged
    if (!this.isArrowClicked(edge) || !edge) {
      return;
    }

    removeEdgeElement(edge.source, edge.target);

    this.dragEdge(edge);

    this.setState({
      draggingEdge: true,
      draggedEdge: edge,
    });
  }

  getMouseCoordinates() {
    const mouseCoordinates = {
      x: 0,
      y: 0,
    };

    if (this.selectedView) {
      const [x, y] = d3.mouse(this.selectedView.node());

      mouseCoordinates.x = x;
      mouseCoordinates.y = y;
    }

    return mouseCoordinates;
  }

  dragEdge(draggedEdge?: EdgeType) {
    const { draggedEdge: draggedEdgeState } = this.state;

    draggedEdge = draggedEdge || draggedEdgeState;

    if (!draggedEdge) {
      return;
    }

    const targetPosition = this.getMouseCoordinates();
    const offset = calculateOffset(
      this.getNodeById(draggedEdge.source).node,
      targetPosition,
      this.viewWrapper.current,
    );

    targetPosition.x += offset.xOff;
    targetPosition.y += offset.yOff;

    this.syncRenderEdge({
      source: draggedEdge.source,
      targetPosition,
    });

    this.setState({
      draggedEdge,
      draggingEdge: true,
    });
  }

  // View 'zoom' handler
  handleZoom = () => {
    const { draggingEdge, viewTransform } = this.state;
    const { transform } = d3.event;

    if (!draggingEdge) {
      d3.select(this.view).attr('transform', transform);

      // prevent re-rendering on zoom
      if (viewTransform !== transform) {
        this.setState({
          viewTransform: transform,
          draggedEdge: null,
          draggingEdge: false,
        }, () => {
          // force the child components which are related to zoom level to update
          this.renderGraphControls();
        });
      }
    } else if (draggingEdge) {
      this.dragEdge();
    }
  }

  handleZoomEnd = () => {
    const { draggingEdge, draggedEdge } = this.state;

    if (!draggingEdge || !draggedEdge) {
      if (draggingEdge && !draggedEdge) {
        // This is a bad case, sometimes when the graph loses focus while an edge
        // is being created it doesn't set draggingEdge to false. This fixes that case.
        this.setState({ draggingEdge: false });
      }

      return;
    }

    // Zoom start events also handle edge clicks. We need to determine if an edge
    // was clicked and deal with that scenario.

    // remove custom edge
    removeElementFromDom('edge-custom-container');

    this.setState({
      draggedEdge: null,
      draggingEdge: false,
    }, () => {
      // handle creating or swapping edges
      const sourceNodeById = this.getNodeById(draggedEdge.source);
      const targetNodeById = this.getNodeById(draggedEdge.target);

      if (!sourceNodeById || !targetNodeById) {
        return;
      }

      this.syncRenderEdge(draggedEdge);
    });
  }

  // Zooms to contents of this.refs.entities
  handleZoomToFit = () => {
    const { minZoom = 0, maxZoom = 2, zoomDur } = this.props;
    const { clientWidth: width, clientHeight: height } = d3.select(this.viewWrapper.current).node();

    const entities = d3.select(this.entities).node();
    const viewBBox = entities.getBBox && entities.getBBox();

    if (!viewBBox) {
      return;
    }

    const next = {
      k: (minZoom + maxZoom) / 2,
      x: 0,
      y: 0,
    };

    if (viewBBox.width > 0 && viewBBox.height > 0) {
      // There are entities
      const dx = viewBBox.width;
      const dy = viewBBox.height;
      const x = viewBBox.x + dx / 2;
      const y = viewBBox.y + dy / 2;

      next.k = 0.9 / Math.max(dx / width, dy / height);

      if (next.k < minZoom) {
        next.k = minZoom;
      } else if (next.k > maxZoom) {
        next.k = maxZoom;
      }

      next.x = width / 2 - next.k * x;
      next.y = height / 2 - next.k * y;
    }

    this.setZoom(next.k, next.x, next.y, zoomDur);
  }

  // Updates current viewTransform with some delta
  modifyZoom = (modK: number = 0, modX: number = 0, modY: number = 0, dur: number = 0) => {
    const { viewTransform } = this.state;
    const { clientWidth: width, clientHeight: height } = d3.select(this.viewWrapper.current).node();
    const center = {
      x: width / 2,
      y: height / 2,
    };

    const next = {
      k: viewTransform.k,
      x: viewTransform.x,
      y: viewTransform.y,
    };

    const targetZoom = next.k * (1 + modK);
    next.k = targetZoom;

    const extent = this.zoom.scaleExtent();
    if (targetZoom < extent[0] || targetZoom > extent[1]) {
      return;
    }

    const translate0 = {
      x: (center.x - next.x) / next.k,
      y: (center.y - next.y) / next.k,
    };

    const l = {
      x: translate0.x * next.k + next.x,
      y: translate0.y * next.k + next.y,
    };

    next.x += center.x - l.x + modX;
    next.y += center.y - l.y + modY;

    this.setZoom(next.k, next.x, next.y, dur);
  }

  // Programmatically resets zoom
  setZoom(k: number = 1, x: number = 0, y: number = 0, dur: number = 0) {
    const t = d3.zoomIdentity.translate(x, y).scale(k);

    d3
      .select(this.viewWrapper.current)
      .select('svg')
      .transition()
      .duration(dur)
      .call(this.zoom.transform, t);
  }

  // Renders 'graph' into view element
  renderView() {
    const { viewTransform } = this.state;

    // Update the view w/ new zoom/pan
    this.selectedView.attr('transform', viewTransform);

    clearTimeout(this.renderNodesTimeout);
    this.renderNodesTimeout = setTimeout(this.renderNodes);
  }

  renderBackground = () => {
    const { gridSize, backgroundFillId, renderBackground } = this.props;

    if (renderBackground) {
      return renderBackground(gridSize);
    }

    return (
      <Background
        gridSize={gridSize}
        backgroundFillId={backgroundFillId}
      />
    );
  }

  getNodeComponent(id: string, node: NodeType) {
    const {
      selectedNodeObj, sourceNode, isLinkingStarted, isResizingStarted,
    } = this.state;
    const {
      onCreateNodeWithEdge, onCollapseNode, onLockNode, onResizeNode, onNodeFocused,
    } = this.props;

    return (
      <Node
        key={id}
        id={id}
        data={node}
        onNodeMove={this.handleNodeMove}
        onNodeUpdate={this.handleNodeUpdate}
        onNodeSelected={this.handleNodeSelected}
        onNodeFocused={onNodeFocused}
        onNodeCollapsed={onCollapseNode}
        onNodeResizeEnded={onResizeNode}
        onNodeResizeStarted={this.startResizing}
        onNodeLocked={onLockNode}
        onNodeLink={this.toggleDraggingEdgeByIcon}
        onCreateNodeWithEdge={onCreateNodeWithEdge}
        isLinkingStarted={isLinkingStarted}
        isResizingStarted={isResizingStarted}
        isLinkSource={isLinkingStarted && sourceNode.id === node.id}
        isSelected={selectedNodeObj.node === node}
        layoutEngine={this.layoutEngine}
        viewWrapperElem={this.viewWrapper.current}
      />
    );
  }

  renderNode = (id: string, element: React.Element) => {
    if (!this.entities) {
      return;
    }

    const containerId = `${id}-container`;
    let nodeContainer = document.getElementById(containerId);

    if (!nodeContainer) {
      nodeContainer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
      nodeContainer.id = containerId;

      this.entities.appendChild(nodeContainer);
    }

    // ReactDOM.render replaces the insides of an element This renders the element
    // into the nodeContainer
    ReactDOM.render(element, nodeContainer);
  }

  renderConnectedEdgesFromNode(node: INodeMapNode, nodeMoving: boolean = false) {
    const { draggingEdge } = this.state;

    if (draggingEdge) {
      return;
    }

    node.incomingEdges
      .forEach(edge => this.syncRenderEdge(edge, nodeMoving));
    node.outgoingEdges
      .forEach(edge => this.syncRenderEdge(edge, nodeMoving));
  }

  asyncRenderNode(node: NodeType) {
    const timeoutId = `nodes-${node.id}`;
    cancelAnimationFrame(this.nodeTimeouts[timeoutId]);

    this.nodeTimeouts[timeoutId] = requestAnimationFrame(() => {
      this.syncRenderNode(node);
    });
  }

  syncRenderNode(node: NodeType) {
    const id = `node-${node.id}`;
    const element: any = this.getNodeComponent(id, node);
    const nodesMapNode = this.getNodeById(node.id);
    if (nodesMapNode) {
      this.renderConnectedEdgesFromNode(nodesMapNode);
    }
    this.renderNode(id, element);
  }

  renderNodes = () => {
    if (!this.entities) {
      return;
    }

    const { nodes } = this.state;

    nodes.forEach(node => this.asyncRenderNode(node));
  }

  isEdgeSelected = (edge: EdgeType) => {
    const { selectedEdgeObj } = this.state;

    return !!selectedEdgeObj
      && !!selectedEdgeObj.edge
      && selectedEdgeObj.edge.source === edge.source
      && selectedEdgeObj.edge.target === edge.target;
  }

  getEdgeComponent = (edge: EdgeType | any) => {
    const { isLinkingStarted } = this.state;
    const { edgeTypes, edgeDefaults } = this.props;
    const srcNodeMap = this.getNodeById(edge.source);
    const sourceNode = srcNodeMap ? srcNodeMap.node : null;
    const trgNodeMap = this.getNodeById(edge.target);
    const targetNode = trgNodeMap ? trgNodeMap.node : null;

    return (
      <Edge
        data={edge}
        edgeTypes={edgeTypes}
        edgeDefaults={edgeDefaults}
        sourceNode={sourceNode}
        targetNode={targetNode || edge.targetPosition}
        viewWrapperElem={this.viewWrapper.current}
        isSelected={this.isEdgeSelected(edge)}
        isLinkingStarted={isLinkingStarted}
        hasSibling={this.checkIfEdgeHasSibling(edge)}
      />
    );
  }

  checkIfEdgeHasSibling = (edge: EdgeType | any) => {
    const { edges } = this.props;
    const { source: edgeSource, target: edgeTarget } = edge;

    return edges.some(({ source, target }) => source === edgeTarget && target === edgeSource);
  }

  renderEdge(id: string, element: any, edge: EdgeType, nodeMoving: boolean = false) {
    if (!this.entities) {
      return;
    }

    const { draggedEdge } = this.state;
    const { afterRenderEdge } = this.props;

    let containerId = `${id}-container`;
    const customContainerId = `${id}-custom-container`;
    let edgeContainer = document.getElementById(containerId);

    if (nodeMoving && edgeContainer) {
      edgeContainer.style.display = 'none';
      containerId = `${id}-custom-container`;
      edgeContainer = document.getElementById(containerId);
    } else if (edgeContainer) {
      const customContainer = document.getElementById(customContainerId);
      edgeContainer.style.display = '';
      if (customContainer) {
        customContainer.remove();
      }
    }

    if (!edgeContainer && edge !== draggedEdge) {
      const newSvgEdgeContainer = document.createElementNS('http://www.w3.org/2000/svg', 'g');
      newSvgEdgeContainer.id = containerId;
      if (this.entities.firstChild) {
        this.entities.insertBefore(newSvgEdgeContainer, this.entities.firstChild);
      } else {
        this.entities.appendChild(newSvgEdgeContainer);
      }
      edgeContainer = newSvgEdgeContainer;
    }

    // ReactDOM.render replaces the insides of an element This renders the element
    // into the edgeContainer
    if (edgeContainer) {
      ReactDOM.render(element, edgeContainer);
      if (afterRenderEdge) {
        afterRenderEdge(id, element, edge, edgeContainer, this.isEdgeSelected(edge));
      }
    }
  }

  asyncRenderEdge = (edge: EdgeType | any, nodeMoving: boolean = false) => {
    const isEdgeIncomplete = !edge.source || (!edge.target && !edge.targetPosition);

    if (isEdgeIncomplete) {
      return;
    }

    const timeoutId = `edges-${edge.source}-${edge.target}`;
    cancelAnimationFrame(this.edgeTimeouts[timeoutId]);

    this.edgeTimeouts[timeoutId] = requestAnimationFrame(() => {
      this.syncRenderEdge(edge, nodeMoving);
    });
  }

  syncRenderEdge(edge: EdgeType | any, nodeMoving: boolean = false) {
    if (!edge.source) {
      return;
    }

    // We have to use the 'custom' id when we're drawing a new node
    const idVar = edge.target ? `${edge.source}-${edge.target}` : 'custom';
    const id = `edge-${idVar}`;
    const element = this.getEdgeComponent(edge);
    this.renderEdge(id, element, edge, nodeMoving);
  }

  renderEdges = () => {
    const { edges, draggingEdge } = this.state;

    if (!this.entities || draggingEdge) {
      return;
    }

    edges.forEach(edge => this.asyncRenderEdge(edge));
  }

  /*
   * ZoomControls is a special child component. To maximize responsiveness we disable
   * rendering on zoom level changes, but this component still needs to update.
   * This function ensures that it updates into the container quickly upon zoom changes
   * without causing a full GraphView render.
   */
  renderGraphControls() {
    const { viewTransform } = this.state;
    const { minZoom, maxZoom } = this.props;
    const zoomControlsRef = document.querySelector(`#${ZOOM_CONTROLS_ID}`);

    if (zoomControlsRef) {
      ReactDOM.render(
        <ZoomControls
          minZoom={minZoom}
          maxZoom={maxZoom}
          zoomLevel={viewTransform ? viewTransform.k : 1}
          zoomToFit={this.handleZoomToFit}
          modifyZoom={this.modifyZoom}
        />,
        zoomControlsRef,
      );
    }
  }

  layoutEngine: any;

  nodeTimeouts: any;

  edgeTimeouts: any;

  renderNodesTimeout: any;

  renderEdgesTimeout: any;

  zoom: any;

  viewWrapper: React.RefObject<HTMLDivElement>;

  graphSvg: React.RefObject<SVGElement>;

  entities: any;

  selectedView: any;

  view: any;

  render() {
    const {
      edgeArrowSize, gridSpacing, gridDotSize, edgeTypes, renderDefs, cursor,
    } = this.props;

    return (
      <ViewWrapper ref={this.viewWrapper}>
        <GraphWrapper ref={this.graphSvg}>
          <Defs
            edgeArrowSize={edgeArrowSize}
            gridSpacing={gridSpacing}
            gridDotSize={gridDotSize}
            edgeTypes={edgeTypes}
            renderDefs={renderDefs}
          />
          <View
            cursor={cursor}
            ref={(el) => { this.view = el; }}
          >
            {this.renderBackground()}

            <g ref={(el) => { this.entities = el; }} />
          </View>
        </GraphWrapper>
      </ViewWrapper>
    );
  }
}

const mapStateToProps = ({ constructor, defaults }) => ({
  cursor: constructor.cursor,
  edgeDefaults: defaults.edgeBody,
});

const mapDispatchToProps = dispatch => ({
  ACTION_SET_CURSOR: (cursor: string) => {
    dispatch(constructorActions.ACTION_SET_CURSOR(cursor));
  },
});

export default connect(mapStateToProps, mapDispatchToProps)(GraphView);
