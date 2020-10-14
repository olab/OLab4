import createInstance from '../createCustomInstance';
import {
  mapFromServer, nodeFromServer, edgeFromServer, mapFromServerOnCreate,
} from '../../helpers/applyAPIMapping';

const API = createInstance();

export const getMap = mapId => API
  .get(`/olab/maps/${mapId}/nodes`)
  .then(({ data: { data: map } }) => mapFromServer(map))
  .catch((error) => {
    throw error;
  });

export const createMap = templateId => API
  .post('/olab/maps', {
    data: {
      ...(templateId && { templateId }),
    },
  })
  .then(({ data: { data: { map } } }) => mapFromServerOnCreate(map))
  .catch((error) => {
    if (!error.response || error.response.status !== 401) {
      throw error;
    }
  });

export const extendMap = (mapId, templateId) => API
  .post(`/olab/maps/${mapId}`, {
    data: {
      templateId,
    },
  })
  .then(({ data: { data: { nodes, links } } }) => ({
    extendedNodes: nodes.map(node => nodeFromServer(node)),
    extendedEdges: links.map(edge => edgeFromServer(edge)),
  }))
  .catch((error) => {
    throw error;
  });
