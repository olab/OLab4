import styled, { css } from 'styled-components';

import { DARK_BLUE, LIGHT_BLUE } from '../../../shared/colors';

export const LabTitleItem = styled.div`
  display: flex;
  font-family: SF Pro Display, serif;
  line-height: normal;
  font-size: 16px;
  text-align: center;
  letter-spacing: 0.06em;
  color: ${DARK_BLUE};
  margin-right: 15px;
  align-items: center;
`;

export const pseudoCommonBlocks = css`
  display: block;
  height: 60%;
  width: 1px;
  background: ${DARK_BLUE};
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
`;

const labItemCommonStyles = css`
  margin-right: 5px;
`;

export const LabIcon = styled.img`
  ${labItemCommonStyles}
`;

export const LabTitle = styled.span`
  ${labItemCommonStyles}
`;

export const Block = styled.div`
  display: flex;
  align-items: center;
`;

export const ContainerWithPseudoBlocks = styled(Block)`
  position: relative;
  margin: 0 8px;

  &::before {
    content: '';
    left: 0;
    ${pseudoCommonBlocks};
  }
  &:after {
    content: '';
    right: 0;
    ${pseudoCommonBlocks};
  }
`;

const styles = () => ({
  positionRelative: {
    position: 'relative',
    backgroundColor: LIGHT_BLUE,
    height: 50,
    display: 'flex',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    boxShadow: 'none',
    borderBottom: '1px solid #c3c3c3',
  },
  root: {
    left: 0,
    top: 88,
    height: 'calc(100vh - 50px)',
    width: 50,
    background: LIGHT_BLUE,
    boxShadow: 'none',
    borderRight: '1px solid #c3c3c3',
    '&.full-screen': {
      top: 39,
    },
  },
});

export default styles;
