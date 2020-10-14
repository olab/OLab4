import createInstance from '../createCustomInstance';
import { templateFromServer } from '../../helpers/applyAPIMapping';

const API = createInstance();

export const getTemplates = () => API
  .get('/olab/templates')
  .then(({ data: { data: templates } }) => templates)
  .catch((error) => {
    throw error;
  });

export const getTemplateDetail = templateId => API
  .get(`/olab/templates/${templateId}`)
  .then(({ data: { data: template } }) => templateFromServer(template))
  .catch((error) => {
    throw error;
  });

export const createTemplate = (mapId, templateName) => API
  .post('/olab/templates', {
    data: {
      mapId,
      name: templateName,
    },
  })
  .then(({ data: { data: { id: templateId } } }) => templateId)
  .catch((error) => {
    throw error;
  });
