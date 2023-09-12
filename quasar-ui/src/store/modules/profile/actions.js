import { api } from "@controleonline/../../src/boot/api";
import * as types from './mutation_types';

export const updateProfile = ({ commit }, { id, component, payload }) => {
  const params = {
    method: 'PUT',
   
    body: (payload)
  }

  return api.fetch(`people/${id}/profile/${component}`, params)
    
    .then(data => {
      return data
    })
}

export const getPhones = ({ commit }, params = {}) => {
  commit(types.SET_ISLOADING)

  return api.fetch('/phones', { params })
    
    .then(data => {
      commit(types.SET_ISLOADING, false)

      return data['hydra:member']
    })
}

export const getAddress = ({ commit }, params = {}) => {
  commit(types.SET_ISLOADING)

  return api.fetch('/addresses', { params })
    
    .then(data => {
      commit(types.SET_ISLOADING, false)

      return data['hydra:member']
    })
}

export const getEmails = ({ commit }, params = {}) => {
  commit(types.SET_ISLOADING)

  return api.fetch('/emails', { params })
    
    .then(data => {
      commit(types.SET_ISLOADING, false)

      return data['hydra:member']
    })
}

export const getUsers = ({ commit }, params = {}) => {
  commit(types.SET_ISLOADING)

  return api.fetch('/users', { params })
    
    .then(data => {
      commit(types.SET_ISLOADING, false)

      return data['hydra:member']
    })
}

export const getDocuments = ({ commit }, params = {}) => {
  commit(types.SET_ISLOADING)

  return api.fetch('/documents', { params })
    
    .then(data => {
      commit(types.SET_ISLOADING, false)

      return data['hydra:member']
    })
}

export const getEmployees = ({ commit }, params = {}) => {
  commit(types.SET_ISLOADING)

  return api.fetch('/people', { params })
    
    .then(data => {
      commit(types.SET_ISLOADING, false)

      return data['hydra:member']
    })
}

export const getBilling = ({ commit }, companyId) => {
  commit(types.SET_ISLOADING)

  return api.fetch(`/people/${companyId}`)
    
    .then(data => {
      commit(types.SET_ISLOADING, false)

      if (data) {
        return {
          amount: data.billing,
          period: data.billingDays,
          dueDays: data.paymentTerm
        }
      }

      return null
    })
}
