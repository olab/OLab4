import createInstance from '../createCustomInstance';
import { nodeToServer, nodeFromServer, edgeFromServer } from '../../helpers/applyAPIMapping';

const API = createInstance();

export const getNode = (mapId, nodeId) => API
  .get(`/olab/maps/${mapId}/nodes/${nodeId}`)
  .then(({ data: { data: node } }) => nodeFromServer(node))
  .catch((error) => {
    throw error;
  });

export const getNodes = mapId => API
  .get(`/olab/maps/${mapId}/nodes`)
  .then(({ data: { data: { nodes, links } } }) => ({
    nodes: nodes.map(node => nodeFromServer(node)),
    edges: links.map(link => edgeFromServer(link)),
  }))
  .catch((error) => {
    throw error;
  });

export const createNode = (mapId, position, sourceNodeId) => API
  .post(`/olab/maps/${mapId}/nodes`, {
    data: {
      ...position,
      ...(sourceNodeId && { sourceId: sourceNodeId }),
    },
  })
  .then(({ data: { data } }) => {
    const { id: newNodeId, links } = data;

    if (links) {
      const { id: newEdgeId } = links;

      return {
        newNodeId,
        newEdgeId,
      };
    }

    return newNodeId;
  })
  .catch((error) => {
    throw error;
  });

export const updateNode = (mapId, updatedNode) => API
  .put(`/olab/maps/${mapId}/nodes/${updatedNode.id}`, {
    data: nodeToServer(updatedNode),
  })
  .catch((error) => {
    throw error;
  });

export const deleteNode = (mapId, nodeId) => API
  .delete(`/olab/maps/${mapId}/nodes/${nodeId}`)
  .catch((error) => {
    throw error;
  });
