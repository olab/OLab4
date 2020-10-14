// @flow
import * as d3 from 'd3';
import crosshair from '../../../../shared/assets/icons/crosshair.cur';

import type { Edge as EdgeType } from '../Edge/types';
import type { Node as NodeType } from '../Node/types';

/**
 *
 *
 * @param {Array<NodeType>} arr
 * @returns
 * @memberof GraphUtils
 *
 * Converts an array of nodes to a hash map.
 */
export const getNodesMap = (arr: Array<NodeType>) => {
  const map = {};
  let item = null;
  for (let i = 0; i < arr.length; i += 1) {
    item = arr[i];
    map[`key-${item.id}`] = {
      children: [],
      incomingEdges: [],
      node: item,
      originalArrIndex: i,
      outgoingEdges: [],
      parents: [],
    };
  }
  return map;
};

/**
 *
 *
 * @param {Array<EdgeType>} arr
 * @returns
 * @memberof GraphUtils
 *
 * Converts an array of edges to a hash map.
 */
export const getEdgesMap = (arr: Array<EdgeType>) => {
  const map = {};
  let item = null;
  for (let i = 0; i < arr.length; i += 1) {
    item = arr[i];
    if (item.target) {
      map[`${item.source || ''}_${item.target}`] = {
        edge: item,
        originalArrIndex: i,
      };
    }
  }
  return map;
};

/**
 *
 *
 * @param {*} nodesMap
 * @param {Array<EdgeType>} edges
 * @memberof GraphUtils
 *
 * Not a pure method that fills various properties of a nodesMap.
 */
export const linkNodesAndEdges = (nodesMap: any, edges: Array<EdgeType>) => {
  let nodeMapSourceNode = null;
  let nodeMapTargetNode = null;
  let edge = null;
  for (let i = 0; i < edges.length; i += 1) {
    edge = edges[i];
    if (edge.target) {
      nodeMapSourceNode = nodesMap[`key-${edge.source || ''}`];
      nodeMapTargetNode = nodesMap[`key-${edge.target}`];
      // avoid an orphaned edge
      if (nodeMapSourceNode && nodeMapTargetNode) {
        nodeMapSourceNode.outgoingEdges.push(edge);
        nodeMapTargetNode.incomingEdges.push(edge);
        nodeMapSourceNode.children.push(nodeMapTargetNode);
        nodeMapTargetNode.parents.push(nodeMapSourceNode);
      }
    }
  }
};

/**
 *
 *
 * @param {string} id
 * @returns
 * @memberof GraphUtils
 *
 * Removes an element from DOM using an id.
 */
export const removeElementFromDom = (id: string): boolean => {
  const container = document.getElementById(id);
  if (container && container.parentNode) {
    container.parentNode.removeChild(container);
    return true;
  }
  return false;
};

/**
 *
 *
 * @param {*} element
 * @param {string} selector
 * @returns
 * @memberof GraphUtils
 *
 * Returns the element if an element matches a selector.
 */
export const findParent = (element: HTMLElement, selector: string) => {
  if (element && element.closest) {
    return element.closest(selector);
  }

  return null;
};

export const yieldingLoop = (
  count: number,
  chunkSize: number,
  callback: Function,
  finished: Function,
) => {
  let i = 0;
  const chunk = () => {
    const end = Math.min(i + chunkSize, count);
    for (; i < end; i += 1) {
      callback.call(null, i);
    }
    if (i < count) {
      setTimeout(chunk, 0);
    } else if (finished) {
      finished.call(null);
    }
  };

  chunk();
};

/**
 *
 *
 * @param {*} prevNode
 * @param {*} newNode
 * @returns
 * @memberof GraphUtils
 *
 * Finds shallow differences in 2 objects.
 */
export const hasNodeShallowChanged = (prevNode: NodeType, newNode: NodeType): boolean => {
  const prevNodeKeys = Object.keys(prevNode);
  const newNodeKeys = Object.keys(prevNode);
  const checkedKeys = {};
  for (let i = 0; i < prevNodeKeys.length; i += 1) {
    const key = prevNodeKeys[i];
    if (!{}.hasOwnProperty.call(newNode, key) || prevNode[key] !== newNode[key]) {
      return true;
    }
    checkedKeys[key] = true;
  }
  for (let i = 0; i < newNodeKeys.length; i += 1) {
    const key = newNodeKeys[i];
    if (!checkedKeys[key]) {
      if (!{}.hasOwnProperty.call(prevNode, key) || prevNode[key] !== newNode[key]) {
        return true;
      }
    }
  }
  return false;
};

/**
 *
 *
 * @param {string} source
 * @param {string} target
 * @memberof GraphView
 *
 * Removes Edge from DOM.
 */
export const removeEdgeElement = (source: string | number, target: string | number) => {
  const id = `${source}-${target}`;
  removeElementFromDom(`edge-${id}-container`);
};

/**
 *
 *
 * @param {*} element
 * @returns
 * @memberof GraphView
 *
 * Checks whether clicked item is in the edge.
 */
export const isPartOfEdge = (element: HTMLElement): boolean => !!findParent(element, '.edge-container');

/**
 *
 *
 * @returns
 * @memberof GraphView
 *
 * Stops zoom whether if ctrl or some button on keyboard is pressed.
 */
export const zoomFilter = (): boolean => {
  const { button, ctrlKey } = d3.event;

  if (button || ctrlKey) {
    return false;
  }

  return true;
};

export const getClickedDOMNodeId = () => {
  const { sourceEvent: sourceEventD3, target: targetD3 } = d3.event;
  const target = sourceEventD3 ? sourceEventD3.target : targetD3;
  const targetNodeDOM = target.closest('g[id^="node-"]');

  if (targetNodeDOM) {
    const nodeDOMId = targetNodeDOM.id.replace('node-', '');

    return nodeDOMId;
  }

  return null;
};

export const setCursorCSS = (cursor: string): string => {
  switch (cursor) {
    case 'customCrosshair':
      return `url('${crosshair}') 12 12, auto`;
    default:
      return cursor;
  }
};

export default {
  setCursorCSS,
};
