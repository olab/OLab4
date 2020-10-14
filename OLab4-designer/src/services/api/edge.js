import createInstance from '../createCustomInstance';
import { edgeToServer } from '../../helpers/applyAPIMapping';

const API = createInstance();

const createEdgeBody = id => ({ data: { destinationId: id } });

export const createEdge = (mapId, edgeData) => API
  .post(
    `/olab/maps/${mapId}/nodes/${edgeData.source}/links`,
    createEdgeBody(edgeData.target),
  )
  .then(({ data: { data: { id } } }) => id)
  .catch((error) => {
    throw error;
  });

export const deleteEdge = (mapId, edgeId, nodeId) => API
  .delete(`/olab/maps/${mapId}/nodes/${nodeId}/links/${edgeId}`)
  .catch((error) => {
    throw error;
  });


export const updateEdge = (mapId, updatedEdgeData) => API
  .put(
    `/olab/maps/${mapId}/nodes/${updatedEdgeData.source}/links/${updatedEdgeData.id}`,
    { data: edgeToServer(updatedEdgeData) },
  )
  .catch((error) => {
    throw error;
  });

export const getEdge = (mapId, nodeId, edgeId) => API
  .get(`/olab/maps/${mapId}/nodes/${nodeId}/links/${edgeId}`)
  .then(({ data }) => data)
  .catch((error) => {
    throw error;
  });
