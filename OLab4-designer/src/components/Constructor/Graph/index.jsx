// @flow
/*
  This component is used as wrapper above GraphView.
  It provides necessary methods into its child.
*/
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { DropTarget } from 'react-dnd';

import GraphView from './GraphView';

import {
  createNewEdge, createNewNode, spec, collect,
} from './utils';
import { EDGE_TYPES, NODE_CREATION_OFFSET } from './config';
import { DND_CONTEXTS, MODALS_NAMES } from '../../Modals/config';

import * as mapActions from '../../../redux/map/action';
import * as modalActions from '../../../redux/modals/action';
import * as wholeMapActions from '../../../middlewares/app/action';
import * as notificationActions from '../../../redux/notifications/action';

import type { IGraphProps, IGraphState } from './types';
import type { Edge as EdgeType } from './Edge/types';
import type { Node as NodeType } from './Node/types';

import { Wrapper } from './styles';

export class Graph extends Component<IGraphProps, IGraphState> {
  constructor(props: IGraphProps) {
    super(props);

    this.graphViewRef = React.createRef();
    this.graphViewWrapperRef = React.createRef();
  }

  componentDidMount() {
    const { connectDropTarget } = this.props;
    const { current: graphViewWrapperRef } = this.graphViewWrapperRef;

    connectDropTarget(graphViewWrapperRef);
  }

  get getSelectedItem(): NodeType | EdgeType | null {
    const nodeItem = this.getSelectedNode;
    if (nodeItem) {
      return nodeItem;
    }

    const edgeItem = this.getSelectedEdge;
    if (edgeItem) {
      return edgeItem;
    }

    return null;
  }

  get getFocusedNode(): NodeType | null {
    const { map: { nodes } } = this.props;
    return nodes.find(({ isFocused }) => isFocused) || null;
  }

  get getSelectedNode(): NodeType | null {
    const { map: { nodes } } = this.props;
    return nodes.find(({ isSelected }) => isSelected) || null;
  }

  get getSelectedEdge(): EdgeType | null {
    const { map: { edges } } = this.props;
    return edges.find(({ isSelected }) => isSelected) || null;
  }

  onNodeFocused = (nodeId: number) => {
    const { ACTION_FOCUS_NODE } = this.props;
    ACTION_FOCUS_NODE(nodeId);
  }

  onSelectNode = (node: NodeType | null) => {
    const { ACTION_SELECT_NODE } = this.props;
    const nodeId = node ? node.id : null;
    const shouldSelectedNode = node && !(node.isSelected);

    if (shouldSelectedNode) {
      ACTION_SELECT_NODE(nodeId);
    }
  };

  onSelectEdge = (edge: EdgeType | null, posX: number = 0, posY: number = 0) => {
    const edgeId = edge ? edge.id : null;
    const {
      ACTION_SELECT_EDGE,
      ACTION_SET_POSITION_MODAL,
    } = this.props;

    if (edge) {
      ACTION_SET_POSITION_MODAL(MODALS_NAMES.LINK_EDITOR_MODAL, posX, posY);
    }

    ACTION_SELECT_EDGE(edgeId);
  };

  onCollapseNode = (nodeId: number) => {
    const { ACTION_UPDATE_NODE_COLLAPSE } = this.props;
    ACTION_UPDATE_NODE_COLLAPSE(nodeId);
  };

  onResizeNode = (nodeId: number, width: number, height: number) => {
    const { ACTION_UPDATE_NODE_RESIZE } = this.props;
    ACTION_UPDATE_NODE_RESIZE(nodeId, width, height);
  }

  onLockNode = (nodeId: number) => {
    const { ACTION_UPDATE_NODE_LOCK } = this.props;
    ACTION_UPDATE_NODE_LOCK(nodeId);
  };

  onCreateNode = (x: number, y: number) => {
    const { mapId, defaults: { nodeBody }, ACTION_CREATE_NODE } = this.props;
    const newNode = createNewNode(mapId, x, y, nodeBody);

    ACTION_CREATE_NODE(newNode);
  }

  onCreateEdge = (sourceNode: NodeType, targetNode: NodeType) => {
    if (sourceNode.id === targetNode.id) {
      return;
    }

    const { defaults: { edgeBody }, ACTION_CREATE_EDGE } = this.props;
    const newEdge = createNewEdge(sourceNode.id, targetNode.id, edgeBody);

    ACTION_CREATE_EDGE(newEdge);
  }

  onCreateNodeWithEdge = (x: number, y: number, sourceNode: NodeType) => {
    const {
      mapId, defaults: { edgeBody, nodeBody }, ACTION_CREATE_NODE_WITH_EDGE,
    } = this.props;

    const newNode = createNewNode(mapId, x, y, nodeBody);
    const newEdge = createNewEdge(sourceNode.id, newNode.id, edgeBody);

    ACTION_CREATE_NODE_WITH_EDGE(newNode, newEdge, sourceNode.id);
  }

  onUpdateNode = (node: NodeType) => {
    const { ACTION_UPDATE_NODE } = this.props;
    ACTION_UPDATE_NODE(node);
  }

  onDeleteNode = (node: NodeType) => {
    const { mapId, ACTION_DELETE_NODE_MIDDLEWARE } = this.props;

    ACTION_DELETE_NODE_MIDDLEWARE(mapId, node.id, node.type);
  }

  onDeleteEdge = (edge: EdgeType) => {
    const { ACTION_DELETE_EDGE } = this.props;
    ACTION_DELETE_EDGE(edge.id, edge.source);
  }

  onUndo = () => {
    const { ACTION_UNDO_MAP, isUndoAvailable } = this.props;

    if (!isUndoAvailable) {
      return;
    }

    ACTION_UNDO_MAP();
  }

  onRedo = () => {
    const { ACTION_REDO_MAP, isRedoAvailable } = this.props;

    if (!isRedoAvailable) {
      return;
    }

    ACTION_REDO_MAP();
  }

  onCopySelected = () => {
    const selectedNode = this.getSelectedNode;

    if (!selectedNode) {
      return;
    }

    const x = selectedNode.x + NODE_CREATION_OFFSET;
    const y = selectedNode.y + NODE_CREATION_OFFSET;

    this.setState({
      copiedNode: {
        ...selectedNode,
        x,
        y,
      },
    });
  }

  onPasteSelected = () => {
    const { ACTION_CREATE_NODE } = this.props;

    const { copiedNode } = this.state;
    const newNodeId = Date.now();
    const newNode = {
      ...copiedNode,
      id: newNodeId,
    };

    ACTION_CREATE_NODE(newNode);
  }

  graphViewWrapperRef: { current: null | HTMLDivElement };

  graphViewRef: { current: null | HTMLDivElement };

  render() {
    const {
      isFullScreen, map: { nodes, edges }, minZoom, maxZoom, layoutEngine,
    } = this.props;

    return (
      <Wrapper
        id="graph"
        ref={this.graphViewWrapperRef}
        isFullScreen={isFullScreen}
      >
        <GraphView
          ref={this.graphViewRef}
          minZoom={minZoom / 100}
          maxZoom={maxZoom / 100}
          nodes={nodes}
          edges={edges}
          selected={this.getSelectedItem}
          focused={this.getFocusedNode}
          edgeTypes={EDGE_TYPES}
          onSelectNode={this.onSelectNode}
          onNodeFocused={this.onNodeFocused}
          onCreateNode={this.onCreateNode}
          onCollapseNode={this.onCollapseNode}
          onLockNode={this.onLockNode}
          onResizeNode={this.onResizeNode}
          onUpdateNode={this.onUpdateNode}
          onDeleteNode={this.onDeleteNode}
          onSelectEdge={this.onSelectEdge}
          onCreateEdge={this.onCreateEdge}
          onDeleteEdge={this.onDeleteEdge}
          onUndo={this.onUndo}
          onRedo={this.onRedo}
          onCopySelected={this.onCopySelected}
          onPasteSelected={this.onPasteSelected}
          onCreateNodeWithEdge={this.onCreateNodeWithEdge}
          layoutEngine={layoutEngine}
        />
      </Wrapper>
    );
  }
}

const mapStateToProps = ({
  map, mapDetails, defaults, constructor,
}) => ({
  map,
  mapId: mapDetails.id,
  defaults,
  minZoom: constructor.zoom.minZoom,
  maxZoom: constructor.zoom.maxZoom,
  isUndoAvailable: !!map.undo.length,
  isRedoAvailable: !!map.redo.length,
  layoutEngine: constructor.layoutEngine,
  isFullScreen: constructor.isFullScreen,
});

const mapDispatchToProps = dispatch => ({
  ACTION_SELECT_EDGE: (edgeId: number) => {
    dispatch(mapActions.ACTION_SELECT_EDGE(edgeId));
  },
  ACTION_DELETE_EDGE: (edgeId: number, nodeId: number) => {
    dispatch(mapActions.ACTION_DELETE_EDGE(edgeId, nodeId));
  },
  ACTION_CREATE_EDGE: (edge: EdgeType) => {
    dispatch(mapActions.ACTION_CREATE_EDGE(edge));
  },
  ACTION_DELETE_NODE_MIDDLEWARE: (mapId: number, nodeId: number, nodeType: number) => {
    dispatch(wholeMapActions.ACTION_DELETE_NODE_MIDDLEWARE(mapId, nodeId, nodeType));
  },
  ACTION_UPDATE_NODE: (node: NodeType) => {
    dispatch(mapActions.ACTION_UPDATE_NODE(node));
  },
  ACTION_CREATE_NODE: (node: NodeType) => {
    dispatch(mapActions.ACTION_CREATE_NODE(node));
  },
  ACTION_CREATE_NODE_WITH_EDGE: (node: NodeType, edge: EdgeType, sourceNodeId: number) => {
    dispatch(mapActions.ACTION_CREATE_NODE_WITH_EDGE(node, edge, sourceNodeId));
  },
  ACTION_SELECT_NODE: (nodeId: number) => {
    dispatch(mapActions.ACTION_SELECT_NODE(nodeId));
  },
  ACTION_FOCUS_NODE: (nodeId: number) => {
    dispatch(mapActions.ACTION_FOCUS_NODE(nodeId));
  },
  ACTION_UPDATE_NODE_COLLAPSE: (nodeId: number) => {
    dispatch(mapActions.ACTION_UPDATE_NODE_COLLAPSE(nodeId));
  },
  ACTION_UPDATE_NODE_RESIZE: (nodeId: number, width: number, height: number) => {
    dispatch(mapActions.ACTION_UPDATE_NODE_RESIZE(nodeId, width, height));
  },
  ACTION_UPDATE_NODE_LOCK: (nodeId: number) => {
    dispatch(mapActions.ACTION_UPDATE_NODE_LOCK(nodeId));
  },
  ACTION_SET_POSITION_MODAL: (modalName: string, x: number, y: number) => {
    dispatch(modalActions.ACTION_SET_POSITION_MODAL(modalName, x, y));
  },
  ACTION_REDO_MAP: () => {
    dispatch(mapActions.ACTION_REDO_MAP());
  },
  ACTION_UNDO_MAP: () => {
    dispatch(mapActions.ACTION_UNDO_MAP());
  },
  ACTION_NOTIFICATION_INFO: (message: string) => {
    dispatch(notificationActions.ACTION_NOTIFICATION_INFO(message));
  },
});

export default DropTarget(
  DND_CONTEXTS.VIEWPORT,
  spec,
  collect,
)(
  connect(
    mapStateToProps,
    mapDispatchToProps,
    null,
    { forwardRef: true },
  )(Graph),
);
