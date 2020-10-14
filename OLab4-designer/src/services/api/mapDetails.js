import createInstance from '../createCustomInstance';
import { mapDetailsToServer, mapDetailsFromServer } from '../../helpers/applyAPIMapping';

const API = createInstance();

export const getMapDetails = mapId => API
  .get(`/olab/maps/${mapId}`)
  .then(({ data: { data: { map } } }) => mapDetailsFromServer(map))
  .catch((error) => {
    throw error;
  });

export const updateMapDetails = ({ id: mapId, ...mapDetails }) => API
  .put(`/olab/maps/${mapId}`, {
    data: { ...mapDetailsToServer(mapDetails) },
  })
  .catch((error) => {
    throw error;
  });
