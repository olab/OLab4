import styled from 'styled-components';

export const TitleContainer = styled.div`
  display: flex;
  align-items: center;
  & > svg {
    margin-right: 5px;
  }
`;

export const TitleText = styled.p`
  text-overflow: ellipsis;
  white-space: nowrap;
  overflow: hidden;
`;
