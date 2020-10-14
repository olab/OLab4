import createInstance from '../createCustomInstance';

const API = createInstance();

export const updateNodeGrid = (mapId, nodes) => API
  .put(`/olab/maps/${mapId}/nodes`, { data: nodes })
  .catch((error) => {
    throw error;
  });

export default {
  updateNodeGrid,
};
