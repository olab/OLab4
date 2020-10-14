// @flow
import React from 'react';

import Switch from '../../../shared/components/Switch';

import { ADVANCED_DETAILS_CHECKBOXES } from './config';

import type { AdvancedDetailsProps as IProps } from './types';

import { ContainerTab } from '../styles';
import { ContainerCheckBox, CheckBox, ContentText } from './styles';

const AdvancedDetails = ({ details, handleCheckBoxChange }: IProps): React$Element<any> => (
  <ContainerTab>
    <ContentText>
      <b>Map Id:</b>
      {` ${details.id}`}
    </ContentText>
    <ContentText>
      <b>OLab version:</b>
      {` ${process.env.PROJECT_VERSION}`}
    </ContentText>
    <ContainerCheckBox>
      {ADVANCED_DETAILS_CHECKBOXES.map(({ label, name }, index) => {
        const key = label + index;

        return (
          <CheckBox key={key}>
            <Switch
              labelPlacement="start"
              name={name}
              label={label}
              checked={details[name]}
              onChange={handleCheckBoxChange}
            />
          </CheckBox>
        );
      })}
    </ContainerCheckBox>
  </ContainerTab>
);

export default AdvancedDetails;
