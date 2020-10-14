import createInstance from '../createCustomInstance';

const API = createInstance();

export const getScopeLevels = level => API
  .get(`/olab/${level}`)
  .then(({ data: { data: scopeLevels } }) => scopeLevels)
  .catch((error) => {
    throw error;
  });

export default {
  getScopeLevels,
};
