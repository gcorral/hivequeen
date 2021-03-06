# Generated by Django 3.1.7 on 2021-06-07 21:04

from django.db import migrations, models
import django.db.models.deletion


class Migration(migrations.Migration):

    initial = True

    dependencies = [
    ]

    operations = [
        migrations.CreateModel(
            name='Client',
            fields=[
                ('id', models.AutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('name', models.CharField(help_text='it001', max_length=200)),
                ('domain', models.CharField(help_text='lab.it.uc3m.es', max_length=200)),
            ],
            options={
                'ordering': ['name', 'domain'],
            },
        ),
        migrations.CreateModel(
            name='Space',
            fields=[
                ('id', models.AutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('name', models.CharField(help_text='4.1B01', max_length=200)),
            ],
            options={
                'ordering': ['name'],
            },
        ),
        migrations.CreateModel(
            name='NetAddress',
            fields=[
                ('id', models.AutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('ip_add', models.GenericIPAddressField()),
                ('client', models.ForeignKey(blank=True, null=True, on_delete=django.db.models.deletion.SET_NULL, to='colony.client')),
            ],
            options={
                'ordering': ['ip_add'],
            },
        ),
        migrations.AddField(
            model_name='client',
            name='space',
            field=models.ForeignKey(null=True, on_delete=django.db.models.deletion.SET_NULL, to='colony.space'),
        ),
    ]
