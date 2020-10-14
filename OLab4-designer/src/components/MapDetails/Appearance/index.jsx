// @flow
import React from 'react';

import OutlinedSelect from '../../../shared/components/OutlinedSelect';

import { ACCESS } from '../config';

import type { AppearanceProps as IProps } from './types';

import { ContainerTab } from '../styles';
import { ContainerSelect } from './styles';

const Appearance = ({ details, themes, handleSelectChange }: IProps): React$Element<any> => {
  const selects = [
    { label: 'Theme', values: themes, name: 'themeId' },
    { label: 'Access', values: ACCESS, name: 'securityType' },
  ];

  return (
    <ContainerTab>
      {selects.map(({ label, name, values }, index) => {
        // TODO: When valid data arrives from the backend, it will be necessary
        //  to delete 'isValidValue' and rename 'numberValue' to 'resultValue'
        const isControlled = name === 'securityType' && details[name] === 4;
        const isValidvalue = !isControlled && (details[name] && (details[name] <= values.length));
        const numberValue = isControlled ? values[details[name] - 2] : values[details[name] - 1];
        const resultValue = isValidvalue || isControlled ? numberValue : values[0];
        const key = label + index;

        return (
          <ContainerSelect key={key}>
            <OutlinedSelect
              name={name}
              label={label}
              labelWidth={80}
              values={values}
              value={resultValue}
              onChange={handleSelectChange}
              fullWidth
            />
          </ContainerSelect>
        );
      })}
    </ContainerTab>
  );
};

export default Appearance;
