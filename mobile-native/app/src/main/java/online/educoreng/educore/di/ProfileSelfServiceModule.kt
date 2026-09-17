package online.educoreng.educore.di

import android.content.Context
import com.squareup.moshi.Moshi
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton
import online.educoreng.educore.core.data.repository.DefaultProfileSelfServiceRepository
import online.educoreng.educore.core.data.repository.ProfileSelfServiceRepository
import online.educoreng.educore.core.network.EduCoreApi

@Module
@InstallIn(SingletonComponent::class)
object ProfileSelfServiceModule {
    @Provides
    @Singleton
    fun provideProfileSelfServiceRepository(
        @ApplicationContext context: Context,
        api: EduCoreApi,
        moshi: Moshi,
    ): ProfileSelfServiceRepository = DefaultProfileSelfServiceRepository(context, api, moshi)
}
