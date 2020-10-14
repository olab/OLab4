import createInstance from '../createCustomInstance';
import { counterGridActionsToServer, counterGridActionsFromServer } from '../../helpers/applyAPIMapping';

const API = createInstance();

export const getCounterGrid = mapId => API
  .get(`/olab/maps/${mapId}/counteractions`)
  .then(({ data: { data: counterGrid } }) => ({
    ...counterGrid,
    actions: counterGrid.actions
      ? counterGrid.actions.map(counterGridActionsFromServer)
      : [],
  }))
  .catch((error) => {
    throw error;
  });

export const updateCounterGrid = (mapId, counterActions) => API
  .put(`/olab/maps/${mapId}/counteractions`,
    { data: counterActions.map(counterGridActionsToServer) })
  .catch((error) => {
    throw error;
  });
