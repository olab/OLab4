// @flow
import React from 'react';

/**
 *
 *
 * @param {*} typesObj
 * @param {Array<*>} graphConfigDefs
 * @memberof Defs
 *
 * This method takes all items that should be stored in <defs /> section
 * and set them key prop(aka list with items).
 * All items should have the following structure:
 {
    <typeOfStructure_1>: {
      <sub-type_1>: {
        shape: <jsx>,
        shapeId: '#${id}',
        ...
      },
      ...
    },
    ...
  }
 */
export const processGraphConfigDefs = (typesObj: any, graphConfigDefs: Array<any>) => {
  Object.values(typesObj).forEach((val) => {
    const safeId = val.shapeId ? val.shapeId.replace('#', '') : 'graphdef';
    graphConfigDefs.push(
      React.cloneElement(val.shape, {
        key: `${safeId}-${graphConfigDefs.length + 1}`,
      }),
    );
  });
};

export default processGraphConfigDefs;
