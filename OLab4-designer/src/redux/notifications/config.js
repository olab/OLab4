export const DEFAULTS = {
  duration: 5000,
  canDismiss: true,
};

export const MESSAGES = {
  ON_UPDATE: {
    SCOPED_OBJECT: 'Object has been updated',
    NODE: 'Node has been updated',
    EDGE: 'Link has been updated',
    MAP: 'Map\'s name successfully updated',
    MAP_DETAILS: 'Details successfully updated',
    COUNTER_ACTIONS: 'Actions have been updated',
    NODE_GRID: 'Nodes have been updated',
  },
  ON_CREATE: {
    TEMPLATE: 'Template has been created',
    SCOPED_OBJECT: 'Object has been created',
  },
  ON_DELETE: {
    NODE: {
      INFO: 'Root node can not be deleted',
    },
    SCOPED_OBJECT: 'Object has been deleted',
  },
};

export const ERROR_MESSAGES = {
  LOCAL_STORAGE: {
    FULL_MEMORY: 'Memory is full. Cannot save.',
  },
};
