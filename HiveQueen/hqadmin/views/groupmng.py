'''
Created on 24 may. 2021

@author: user
'''
from django.contrib.auth import login
from django.shortcuts import redirect
from django.views.generic import CreateView

from hqadmin.forms import GroupmngSignUpForm
from hqadmin.models import User

class GroupmngSignUpView(CreateView):
    model = User
    form_class = GroupmngSignUpForm
    template_name = 'registration/signup_form.html'

    def get_context_data(self, **kwargs):
        kwargs['user_type'] = 'groupmng'
        return super().get_context_data(**kwargs)

    def form_valid(self, form):
        user = form.save()
        login(self.request, user)
        # TODO..:
        return redirect('groupmng')