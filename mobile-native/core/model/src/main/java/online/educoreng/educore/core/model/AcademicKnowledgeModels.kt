package online.educoreng.educore.core.model

data class KnowledgeReadiness(val score: Int, val coverageScore: Int, val qualityScore: Int?, val ready: Boolean, val missing: List<String>, val critical: List<String>)
data class KnowledgeSource(val title: String?, val filename: String?, val resourceType: String?, val priority: Int?, val official: Boolean = false, val primary: Boolean = false)
data class KnowledgeConsolidation(val sourceCount: Int = 0, val topicCount: Int = 0, val usableSourceCount: Int = 0)
data class KnowledgeBlock(val type: String, val sequence: Int, val title: String?, val content: String)
data class KnowledgeFields(val time: String?, val duration: Int?, val averageAge: Int?, val sex: String?, val entryBehaviour: String?, val previousKnowledge: String?, val instructionalResources: String?, val introduction: String?, val studentNoteSummary: String?, val reference: String?)
data class KnowledgeTopic(val id: Long, val className: String, val subject: String, val term: String, val week: Int?, val lesson: Int?, val topic: String, val subTopic: String?, val resourceType: String, val readiness: KnowledgeReadiness, val consolidation: KnowledgeConsolidation? = null, val sources: List<KnowledgeSource> = emptyList(), val fields: KnowledgeFields? = null, val blocks: List<KnowledgeBlock> = emptyList())
data class KnowledgeCatalogue(val topics: List<KnowledgeTopic>, val currentPage: Int, val lastPage: Int, val total: Int)
data class GeneratedKnowledgeDocument(val type: String, val topic: KnowledgeTopic, val title: String, val sections: List<Pair<String, String>>)
data class KnowledgeMutationResult(val message: String, val lessonPlanId: Long?, val revision: Int? = null)
