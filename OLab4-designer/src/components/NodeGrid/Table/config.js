
export const TABLE_HEAD_CELLS = {
  id: {
    label: 'Node ID',
    isSortable: true,
    isEditable: false,
  },
  title: {
    label: 'Title',
    isSortable: true,
    isEditable: true,
  },
  text: {
    label: 'Text',
    isSortable: false,
    isEditable: true,
  },
  x: {
    label: 'X',
    isSortable: true,
    isEditable: true,
  },
  y: {
    label: 'Y',
    isSortable: true,
    isEditable: true,
  },
};

export const ORDER = {
  ASC: 'asc',
  DESC: 'desc',
};

export const DEFAULT_SORT_STATUS = {
  id: ORDER.ASC,
  x: ORDER.ASC,
  y: ORDER.ASC,
  title: ORDER.ASC,
};
